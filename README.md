# php-whois

PHP class to retrieve WHOIS information.

## Example of usage

```php

<?php

$domain = 'reg.ru';
$whois = new \Azurre\Component\Dns\Whois();

//$info = $whois->getInfo($domain);
//print_r($info);

if ($whois->isAvailable($domain)) {
    echo "Domain is available\n";
} else {
    echo "Domain is registered\n";
}

```