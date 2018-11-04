<?php

namespace Azurre\Component\Dns;

/**
 * Class Whois
 */
class Whois
{
    const WHOIS_PORT = 43;

    const BASE_PARSER = 'base';

    /**
     * Max request time in seconds
     *
     * @var int
     */
    protected $timeout = 10;

    /**
     * @var string
     */
    protected $serversListPath = 'whois.servers.json';

    /**
     * List of whois servers
     *
     * @var array
     */
    protected $servers;

    /**
     * @var array
     */
    protected $parser = [
        self::BASE_PARSER => \Azurre\Component\Dns\Parser\Base::class,
        'ru'              => \Azurre\Component\Dns\Parser\Ru::class
    ];

    /**
     * @param array $servers
     */
    public function __construct(array $servers = null)
    {
        $this->servers = $servers;
    }

    /**
     * @param string $domain
     * @return string
     * @throws \Exception
     */
    public function find($domain)
    {
        $domain = mb_strtolower(trim($domain));
        list($subDomain, $tld) = $this->parseDomain($domain);
        if (!$this->isValid($domain)) {
            throw new \Exception('Domain name is not valid');
        }

        return $this->request($this->getServer($tld), $tld, $subDomain);
    }

    /**
     * @param string $domain
     * @return array
     * @throws \Exception
     */
    public function getInfo($domain)
    {
        $response = $this->find($domain);
        $domain = mb_strtolower(trim($domain));
        list(, $tld) = $this->parseDomain($domain);
        $whoisServerData = $this->getServer($tld, false);
        if (isset($whoisServerData[2])) {
            $parserClass = $this->parser[$whoisServerData[2]];
        } else {
            $parserClass = $this->parser[static::BASE_PARSER];
        }
        /** @var \Azurre\Component\Dns\ParserInterface $parser */
        $parser = new $parserClass;

        return $parser->process($response);
    }

    /**
     * @param string $domain
     * @return array
     */
    protected function parseDomain($domain)
    {
        return explode('.', $domain, 2);
    }

    /**
     * @param string $whoisServer
     * @param string $tld
     * @param string $subDomain
     * @return string
     * @throws \Exception
     */
    protected function request($whoisServer, $tld, $subDomain)
    {
        $response = '';
        // if whois server server reply over HTTP protocol instead of WHOIS protocol
        if (strpos($whoisServer, 'http') === 0) {
            // curl session to get whois reposnce
            $ch = curl_init();
            $url = $whoisServer . $subDomain . '.' . $tld;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeout());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            if ($error) {
                throw new \Exception($error);
            }
            $response = strip_tags($response);
            curl_close($ch);
        } else {
            // Getting whois information
            $fp = @fsockopen($whoisServer, static::WHOIS_PORT);
            if (!$fp) {
                if ($error = error_get_last()) {
                    $errorMessage = $error['message'];
                } else {
                    $errorMessage = "Can't connect to {$whoisServer}";
                }
                throw new \Exception($errorMessage);
            }
            $domain = "{$subDomain}.{$tld}";
            fwrite($fp, "{$domain}\r\n");
            // Checking whois server for .com and .net
            if ($tld === 'com' || $tld === 'net') {
                while (!feof($fp)) {
                    $line = trim(fgets($fp, 128));
                    $response .= $line;
                    $lineArr = explode(':', $line);
                    if (strtolower(reset($lineArr)) === 'whois server') {
                        $whoisServer = trim(next($lineArr));
                    }
                }
                // Getting whois information
                $fp = fsockopen($whoisServer, static::WHOIS_PORT);
                if (!$fp) {
                    if ($error = error_get_last()) {
                        $errorMessage = $error['message'];
                    } else {
                        $errorMessage = "Can't connect to {$whoisServer}";
                    }
                    throw new \Exception($errorMessage);
                }
                $domain = $subDomain . '.' . $tld;
                fwrite($fp, "{$domain}\r\n");
                // Getting string
                $response = '';
                while (!feof($fp)) {
                    $response .= fread($fp, 128);
                }
                // Checking for other tld's
            } else {
                while (!feof($fp)) {
                    $response .= fread($fp, 128);
                }
            }
            fclose($fp);
        }

        $encoding = mb_detect_encoding($response, 'UTF-8, ISO-8859-1, ISO-8859-15', true);
        $response = mb_convert_encoding($response, 'UTF-8', $encoding);

        return htmlspecialchars($response, ENT_COMPAT, 'UTF-8', true);
    }

    /**
     * @param string $domain
     * @return bool
     * @throws \Exception
     */
    public function isAvailable($domain)
    {
        $domain = mb_strtolower(trim($domain));
        if (!$this->isValid($domain)) {
            throw new \InvalidArgumentException('Domain name is not valid');
        }
        list($subDomain, $tld) = $this->parseDomain($domain);
        $whoisServerData = $this->getServer($tld, false);

        $response = $this->request(reset($whoisServerData), $tld, $subDomain);
        $notFoundPatten = next($whoisServerData);

        $whois_string2 = @preg_replace('/' . $domain . '/', '', $response);
        $whois_string = @preg_replace("/\s+/", ' ', $response);

        $array = explode(':', $notFoundPatten);
        if (reset($array) === 'MAXCHARS') {
            if (strlen($whois_string2) <= $array[1]) {
                return true;
            } else {
                return false;
            }
        } else {
            if (preg_match('/' . $notFoundPatten . '/i', $whois_string)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param string $domain
     * @return bool
     * @throws \Exception
     */
    public function isValid($domain)
    {
        if (!preg_match('/^([\p{L}\d\-]+)\.((?:[\p{L}\-]+\.?)+)$/ui', $domain)
            && !preg_match('/^(xn\-\-[\p{L}\d\-]+)\.(xn\-\-(?:[a-z\d-]+\.?1?)+)$/ui', $domain)) {
            return false;
        }
        list($subDomain, $tld) = $this->parseDomain($domain);
        if ($this->getServer($tld)) {
            $subDomain = strtolower($subDomain);
            if (preg_match('/^[a-z0-9\-]{2,}$/', $subDomain)
                && !preg_match('/^-|-$/', $subDomain) //&& !preg_match("/--/", $tmp_domain)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(
        $timeout
    ) {
        $this->timeout = $timeout;
    }

    /**
     * @param string $key
     * @return array
     */
    public function getServers($key = null)
    {
        if ($this->servers === null) {
            $path = strpos($this->serversListPath, '/') === 0
                ? $this->serversListPath
                : __DIR__ . '/' . $this->serversListPath;
            $this->servers = json_decode(trim(file_get_contents($path)), true) ?: [];
        }
        if ($key) {
            return isset($this->servers[$key]) ? $this->servers[$key] : null;
        }

        return $this->servers;
    }

    /**
     * @param string $tld
     * @param bool   $getHost
     * @return array|string
     * @throws \Exception
     */
    public function getServer($tld, $getHost = true)
    {
        if ($getHost) {
            $server = $this->getServers($tld);
            if (!$server) {
                throw new \Exception('There is no whois server for this TLD in list!');
            }

            return reset($server);
        }

        return $this->getServers($tld);
    }

    /**
     * @return string
     */
    public function getServersListPath()
    {
        return $this->serversListPath;
    }

    /**
     * @param string $serversListPath
     * @return $this
     */
    public function setServersListPath($serversListPath)
    {
        $this->serversListPath = $serversListPath;

        return $this;
    }

    /**
     * @param array $servers
     * @return $this
     */
    public function setServers(array $servers)
    {
        $this->servers = $servers;

        return $this;
    }

    /**
     * @param array $servers
     * @return $this
     */
    public function addServers(array $servers)
    {
        $this->servers = array_merge($this->servers, $servers);

        return $this;
    }
}
