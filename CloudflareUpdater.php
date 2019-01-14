<?php
class CloudflareUpdater {
	private $zoneName;
	private $httpHeader;
	private $publicIP;
	private $zoneID;
	
	private $recordName;
	private $recordID;
	private $recordIP;
	
	public function __construct($email, $key, $zoneName, $recordName) {
		$this->zoneName = $zoneName;
		$this->httpHeader = array("X-Auth-Email: {$email}", "X-Auth-Key: {$key}", "Content-Type: application/json");
		$this->publicIP = $this->getPublicIP();
		$this->zoneID = $this->getZoneID();
		
		$this->recordName = $recordName;
		$recordData = $this->getRecordData();
		$this->recordID = $recordData["id"];
		$this->recordIP = $recordData["ip"];
		$this->updateRecord();
	}
	
	private function getPublicIP() {
		return file_get_contents("https://api.ipify.org/");
	}
	
	private function getZoneID() {
		$ch = curl_init("https://api.cloudflare.com/client/v4/zones/");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->httpHeader);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
		$json = json_decode(curl_exec($ch), true);
		curl_close($ch);
		$this->onError($json);
		foreach($json["result"] as $zone) {
			if($zone["name"] == $this->zoneName) {
				return $zone["id"];
			}
		}
		print("ERROR\nZone {$this->zoneName} not found\n");
		exit();
	}
	
	private function getRecordData() {
		$ch = curl_init("https://api.cloudflare.com/client/v4/zones/{$this->zoneID}/dns_records?type=A&name={$this->recordName}&page=1&per_page=20&order=type&direction=desc&match=all");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->httpHeader);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
		$json = json_decode(curl_exec($ch), true);
		$this->onError($json);
		curl_close($ch);
		if(count($json["result"]) == 0) {
			print("ERROR\nRecord {$this->recordName} not found\n");
			exit();
		}
		return array("id" => $json["result"][0]["id"], "ip" => $json["result"][0]["content"]);
	}
	
	private function updateRecord() {
		if($this->recordIP == $this->publicIP) {
			print "Record \"{$this->recordName}\" has already the IP address \"{$this->publicIP}\".\n";
			exit();
		}
		
		$postfields = json_encode(
			array(
				"type" => "A", 
				"name" => $this->recordName, 
				"content" => $this->publicIP, 
				"ttl" => 120, 
				"proxied" => true
			)
		);
		
		$ch = curl_init("https://api.cloudflare.com/client/v4/zones/{$this->zoneID}/dns_records/{$this->recordID}");
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->httpHeader);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
		$json = json_decode(curl_exec($ch), true);
		$this->onError($json);
		curl_close($ch);
		
		print "Updated!\n";
	}
	
	private function onError($json) {
		if($json["success"] != 1) {
			print "ERROR\n";
			foreach($json["errors"] as $error) {
				print "Code {$error["code"]}: {$error["message"]}\n";
			}
			exit();
		}
	}
	
}
?>
