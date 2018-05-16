<?php
/**
 * Copyright (c) 2018. AIT
 */

namespace sn\core\console;

use Exception;
use sn\core\BaseKeyStorage;
use sn\core\console\model\ModelConsole;

/**
 * Class KeyStorage
 * @package sn\core\console
 */
class KeyStorage extends BaseKeyStorage{

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
                $res = ModelConsole::Query($q, ['key' => $key]);
                if($res) {
                    $value = ModelConsole::FetchValue($res);
                    if(!is_null($value)) {
                        $this->storage[$key] = $value;

                        return $value;
                    }
                } else {
                    throw new Exception(ModelConsole::Error());
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
            $value = ModelConsole::Sanitize($value);
            $q = "REPLACE INTO key_storage SET `key` = :key, `value` = :value";
            $res = ModelConsole::Query($q, ['key'=>$key, 'value'=>$value]);
            if(!$res)
                throw new Exception(ModelConsole::Error());

            $this->storage[$key] = $value;
        }
    }

    /**
     * @throws \Exception
     */
    public function Init(){
        $q = "SELECT `key`, `value` FROM key_storage";
        $res = ModelConsole::Query($q);
        if($res) {
            while($row = ModelConsole::FetchAssoc($res)) {
                $this->storage[$row['key']] = $row['value'];
            }
        } else {
            throw new Exception(ModelConsole::Error());
        }
    }

}