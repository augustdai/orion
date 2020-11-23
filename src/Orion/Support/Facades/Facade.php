<?php

namespace Orion\Support\Facades;

abstract class Facade
{
    protected static $app;

    /**
     *
     */
    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::$app[static::getFacadeAccessor()];
        return $instance->$method(...$args);
    }

    /**
     * @param $app
     */
    public static function setFacadeApplication($app)
    {
        static::$app = $app;
    }

}