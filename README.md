# php-whois 

PHP class to retrieve WHOIS information.

# Installation

Install composer in your project:
```
curl -s https://getcomposer.org/installer | php
```

Require the package with composer:
```
composer require azurre/php-whois
```

# Usage

```php
<?php
include 'vendor/autoload.php';
$whois = new \Azurre\Component\Dns\Whois();

try {
    // Retrieve raw whois info
    $rawInfo = $whois->find($domain);
    
    // Retrieve parsed whois info
    $info = $whois->getInfo($domain);
    
    if ($whois->isAvailable('google.com')) {
        echo "Domain is available\n";
    } else {
        echo "Domain is registered\n";
    }
} catch (\Exception $e) {
    echo $e->getMessage();
}
```

## Example output:
```
Array
(
    [registrant] => Array
        (
            [organization] =>
            [state] =>
            [country] =>
        )

    [registration] => Array
        (
            [created] => 1997-09-15T04:00:00Z
            [updated] => 2018-02-21T18:36:40Z
            [expires] => 2020-09-14T04:00:00Z
            [registrar] => MarkMonitor Inc.
        )

    [name_servers] => Array
        (
            [0] => NS1.GOOGLE.COM
            [1] => NS2.GOOGLE.COM
            [2] => NS3.GOOGLE.COM
            [3] => NS4.GOOGLE.COM
        )

    [whois] => Array
        (
            [record] =>    Domain Name: GOOGLE.COM
                           Registry Domain ID: 2138514_DOMAIN_COM-VRSN
                           Registrar WHOIS Server: whois.markmonitor.com
                           Registrar URL: http://www.markmonitor.com
                           Updated Date: 2018-02-21T18:36:40Z
                           Creation Date: 1997-09-15T04:00:00Z
                           Registry Expiry Date: 2020-09-14T04:00:00Z
                           Registrar: MarkMonitor Inc.
                           Registrar IANA ID: 292
                           Registrar Abuse Contact Email: abusecomplaints@markmonitor.com
                           Registrar Abuse Contact Phone: +1.2083895740
                           Domain Status: clientDeleteProhibited https://icann.org/epp#clientDeleteProhibited
                           Domain Status: clientTransferProhibited https://icann.org/epp#clientTransferProhibited
                           Domain Status: clientUpdateProhibited https://icann.org/epp#clientUpdateProhibited
                           Domain Status: serverDeleteProhibited https://icann.org/epp#serverDeleteProhibited
                           Domain Status: serverTransferProhibited https://icann.org/epp#serverTransferProhibited
                           Domain Status: serverUpdateProhibited https://icann.org/epp#serverUpdateProhibited
                           Name Server: NS1.GOOGLE.COM
                           Name Server: NS2.GOOGLE.COM
                           Name Server: NS3.GOOGLE.COM
                           Name Server: NS4.GOOGLE.COM
                           DNSSEC: unsigned
                           URL of the ICANN Whois Inaccuracy Complaint Form: https://www.icann.org/wicf/
                        &gt;&gt;&gt; Last update of whois database: 2018-11-07T13:34:45Z &lt;&lt;&lt;

                        For more information on Whois status codes, please visit https://icann.org/epp
        )
)
```
