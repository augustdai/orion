<?php

namespace Orion\Stone\Bootstrap;

use Orion\Stone\Application;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * LoadConfiguration
 * Class LoadConfiguration
 * @package Orion\Stone\Bootstrap
 */
class LoadConfiguration
{
    /**
     * @param Application $app
     */
    public function bootstrap(Application $app)
    {
        // Log
        if (!is_dir(BASE_PATH.'/logs/')) {
            mkdir(BASE_PATH.'/logs/', 0700);
        }

        $monolog = new \Monolog\Logger('system');
        $monolog->pushHandler(new \Monolog\Handler\StreamHandler(BASE_PATH.'/logs/app.log', \Monolog\Logger::ERROR));
        // whoops: php errors for cool kids
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        // $whoops->pushHandler(new \Whoops\Handler\PlainTextHandler($monolog));
        $whoops->register();

        // BASE_URL
        $config = require BASE_PATH.'/config/config.php';
        define('BASE_URL', $config['base_url']);
        mb_internal_encoding('UTF-8');
        date_default_timezone_set($config['time_zone']); // TIME_ZONE

        // Eloquent ORM
        $capsule = new Capsule;
        $capsule->addConnection(require BASE_PATH.'/config/database.php');
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // View Loader
        class_alias('Orion\Routing\Router', 'Route');
        class_alias('\Orion\View\View','View');
    }

}