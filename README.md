# [PHP] Cloudflare IP Updater
PHP class for update your Cloudflare domain IP

(requires PHP-CURL extension)

Easy usage:
```php
// Your email address
$email 		= "";
// Login to Cloudflare -> Go to My Profile. ->  Scroll down to Global API Key -> Click View
$key 		= "";
// Doaminname: example.com
$zoneName 	= "";
// same as zoneName or subdomains like: forum.example.com 
$domainName	= "";
// load class
new CloudflareUpdater($email, $key, $zoneName, $domainName);
```
