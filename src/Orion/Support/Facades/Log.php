<?php

namespace Orion\Support\Facades;

/**
 * Class Log
 * @package Orion\Support\Facades
 */
class Log extends Facade
{
    /**
     * @return string|void
     */
    public static function getFacadeAccessor()
    {
        return 'log';
    }

}