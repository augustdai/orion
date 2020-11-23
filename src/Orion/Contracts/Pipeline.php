<?php

namespace Orion\Contracts;

use Closure;

/**
 * 管道接口
 * Interface Pipeline
 * @package Orion\Contracts
 */
interface Pipeline
{

    /**
     * send
     * @param $traveler
     * @return mixed
     */
    public function send($traveler);

    /**
     * through
     * @param $stops
     * @return mixed
     */
    public function through($stops);

    /**
     * via
     * @param $method
     * @return mixed
     */
    public function via($method);

    /**
     * then
     * @param Closure $destination
     * @return mixed
     */
    public function then(Closure $destination);

}