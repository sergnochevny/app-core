<?php

namespace sn\core;

/**
 * Class BaseKeyStorage
 * @package sn\core
 */
class BaseKeyStorage{

    /**
     * @var array
     */
    protected $storage = [];

    /**
     * @param $name
     * @return mixed|null
     * @throws \Exception
     */
    public function __get($name){
        return $this->get($name);
    }

    /**
     * @param $name
     * @param $value
     * @throws \Exception
     */
    public function __set($name, $value){
        $this->set($name, $value);
    }
}