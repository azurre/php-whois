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
class Ua extends \Azurre\Component\Dns\Parser\Base
{
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
                'organization' => $this->get('org'),
                'state'        => $this->get('Registrant State/Province'),
                'country'      => $this->get('Registrant Country')
            ],
            'registration' => [
                'status'    => $this->get('status'),
                'created'   => $this->get('created'),
                'updated'   => $this->get('modified'),
                'expires'   => $this->get('expires'),
                'registrar' => $this->get('registrar')
            ],
            'name_servers' => $this->get('nserver'),
            'whois'        => [
                'record' => $response
            ]
        ];
    }
}
