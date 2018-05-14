<?php

namespace app\core;

/**
 * Date: 19.12.2017
 * Time: 21:13
 */
interface ErrorHandlerInterface{

    /**
     * @return mixed
     */
    public function handle();
}