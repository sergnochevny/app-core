<?php

namespace sn\core;

use sn\core\model\ModelBase;
use Exception;

/**
 * Class KeyStorage
 * @package sn\core
 */
class KeyStorage extends KeyStorageBase{

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
                $res = ModelBase::Query($q, ['key' => $key]);
                if($res) {
                    $value = ModelBase::FetchValue($res);
                    if(!is_null($value)) {
                        $this->storage[$key] = $value;

                        return $value;
                    }
                } else {
                    throw new Exception(ModelBase::Error());
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
            $value = ModelBase::Sanitize($value);
            $q = "REPLACE INTO key_storage SET `key` = :key, `value` = :value";
            $res = ModelBase::Query($q, ['key' => $key, 'value' => $value]);
            if(!$res)
                throw new Exception(ModelBase::Error());

            $this->storage[$key] = $value;
        }
    }

    /**
     * @throws \Exception
     */
    public function Init(){
        $q = "SELECT `key`, `value` FROM key_storage";
        $res = ModelBase::Query($q);
        if($res) {
            while($row = ModelBase::FetchAssoc($res)) {
                $this->storage[$row['key']] = $row['value'];
            }
        } else {
            throw new Exception(ModelBase::Error());
        }
    }

}