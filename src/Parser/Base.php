<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @date   04.11.2018
 */

namespace Azurre\Component\Dns\Parser;

/**
 * Class Base
 */
class Base implements \Azurre\Component\Dns\ParserInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param string $response
     * @return array
     */
    public function process($response)
    {
        $this->data = $this->parse($response);
        if (empty($this->data)) {
            return [];
        }

        return [
            'registrant'   => [
                'organization' => $this->get('Registrant Organization'),
                'state'        => $this->get('Registrant State/Province'),
                'country'      => $this->get('Registrant Country')
            ],
            'registration' => [
                'created'   => $this->get('Creation Date'),
                'updated'   => $this->get('Updated Date'),
                'expires'   => $this->get('Registry Expiry Date'),
                'registrar' => $this->get('Registrar')
            ],
            'name_servers' => $this->get('Name Server'),
            'whois'        => [
                'record' => $response
            ]
        ];
    }

    /**
     * @param string $response
     * @return array
     */
    protected function parse($response)
    {
        $result = [];
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            $line = explode(':', $line, 2);
            if (count($line) === 2) {
                list($key, $value) = $line;
                $key = trim($key);
                $value = trim($value);
                if (!empty($key) && !empty($value)) {
                    if (isset($result[$key])) {
                        $result[$key] = (array)$result[$key];
                        $result[$key][] = $value;
                    } else {
                        $result[$key] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        $array = $this->data;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return null;
            }
            $array = $array[$segment];
        }

        return $array;
    }
}
