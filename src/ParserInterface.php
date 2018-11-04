<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @date   04.11.2018
 */
namespace Azurre\Component\Dns;

/**
 * Interface ParserInterface
 */
interface ParserInterface
{
    /**
     * @param string $response
     * @return array
     */
    public function process($response);
}
