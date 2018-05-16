<?php

namespace sn\core;

/**
 * Class KeyStorageBase
 * @package sn\core
 */
abstract class KeyStorageBase{

    /**
     * @var array
     */
    protected $storage = [];

    /**
     * @param $key
     * @return mixed
     */
    abstract protected function get($key);

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    abstract protected function set($key, $value);

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