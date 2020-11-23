<?php

namespace Orion\Contracts;

interface Kernel
{

    /**
     * @param $input
     * @param null $output
     * @return mixed
     */
    public function handle($input, $output = null);

    /**
     * Bootstrap the application for HTTP requests.
     * @return mixed
     */
    public function bootstrap();

    /**
     * Get the Laravel application instance.
     * @return mixed
     */
    public function getApplication();
}
