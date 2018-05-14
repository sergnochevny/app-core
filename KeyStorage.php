<?php

namespace sn\core;

use sn\core\model\ModelBase;
use Exception;

/**
 * Class KeyStorage
 * @package sn\core
 */
class KeyStorage{

    /**
     * @var array
     */
    protected $storage = [];

    /**
     * @param $key
     * @return mixed|null
     * @throws \Exception
     */
    protected function get($key){
        if(isset($key)) {
            if(isset($this->storage[$key])) {
                return $this->storage[$key];
            } else {
                $q = "SELECT value FROM key_storage";
                $q .= "  where `key` = :key";
                $res = ModelBase::query($q, ['key' => $key]);
                if($res) {
                    $value = ModelBase::fetch_value($res);
                    if(!is_null($value)) {
                        $this->storage[$key] = $value;

                        return $value;
                    }
                } else {
                    throw new Exception(ModelBase::error());
                }
            }
        }

        return null;
    }

    /**
     * @param $key
     * @param $value
     * @throws \Exception
     */
    protected function set($key, $value){

        if(isset($key) && isset($value)) {
            $value = ModelBase::sanitize($value);
            $q = "REPLACE INTO key_storage SET `key` = :key, `value` = :value";
            $res = ModelBase::query($q, ['key'=>$key, 'value'=>$value]);
            if(!$res)
                throw new Exception(ModelBase::error());

            $this->storage[$key] = $value;
        }
    }

    /**
     * @throws \Exception
     */
    public function init(){
        $q = "SELECT `key`, `value` FROM key_storage";
        $res = ModelBase::query($q);
        if($res) {
            while($row = ModelBase::fetch_assoc($res)) {
                $this->storage[$row['key']] = $row['value'];
            }
        } else {
            throw new Exception(ModelBase::error());
        }
    }

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