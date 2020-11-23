<?php

namespace Orion\Stone\Bootstrap;

use Orion\Stone\Application;

/**
 * LoadConfiguration
 * Class RegisterProviders
 * @package Orion\Stone\Bootstrap
 */
class RegisterProviders
{
    /**
     * @param Application $app
     */
    public function bootstrap(Application $app)
    {
        \Orion\Support\Facades\Facade::setFacadeApplication($app);

        $config = require BASE_PATH .'/config/config.php';
        foreach ($config['aliases'] as $className => $facadeName) {
            class_alias($facadeName, $className);
        }
    }

}