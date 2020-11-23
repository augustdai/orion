<?php

namespace Orion\Http;

/**
 * Class Response
 * @package Orion\Http
 */
class Response
{
  
    public $return;

    /**
     * Response constructor.
     */
    public function __construct() {}

    /**
     *
     */
    public function send()
    {
        \View::process($this->return);
    }

}