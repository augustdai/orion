<?php

namespace Orion\Http;

/**
 * Class Request
 * @package Orion\Http
 */
class Request
{
    public $response;

    /**
     * Request constructor.
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

}