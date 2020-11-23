<?php

namespace Orion\Log;

class LogServiceProvider
{
    public $app;

    /**
     * LogServiceProvider constructor.
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 注册服务
     */
    public function register()
    {
        $this->app->singleton('log', function () {
            return $this->createLogger();
        });
    }

    /**
     * @return LogWriter
     */
    public function createLogger()
    {
        return new \Orion\Log\LogWriter;
    }

}
