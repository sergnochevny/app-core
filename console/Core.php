<?php

namespace app\core\console;

use app\core\DBConnection;
use app\core\exceptions\SelectDBException;
use Closure;
use Exception;
use ReflectionClass;
use ReflectionMethod;

/**
 * class Core
 *
 * @method string server($prm)
 * @method string session($prm)
 * @method string router($prm)
 * @method string db($prm)
 * @method string connections($prm)
 * @method string config(...$prm)
 */
class Core{

    protected $router;
    protected $server;

    protected $db;
    protected $connections;
    protected $config = [];

    /**
     * Core constructor.
     * @param $app
     */
    public function __construct(&$app){
        $app = $this;
        $app->init();
    }

    /**
     *
     */
    private function initConfig(){
        $config = $this->getAppConfig();
        if(is_array($config)) {
            foreach($config as $key => $value) {
                if(function_exists($key)) {
                    if(is_array($value)) {
                        if(count(array_filter(array_keys($value), "is_int")) == count($value)) {
                            call_user_func_array($key, $value);
                        } else {
                            $closure = [];
                            foreach($value as $var => $val) {
                                if($val instanceof Closure)
                                    $closure[$var] = $val;
                                else call_user_func_array($key, [$var, $val]);
                            }
                            foreach($closure as $var => $func) {
                                $val = call_user_func($func);
                                call_user_func_array($key, [$var, $val]);
                            }
                        }
                    } else {
                        call_user_func($key, $value);
                    }
                } else {
                    $this->config($key, $value);
                }
            }
        } else {
            new Exception(
                'Application is not configured...'
            );
        }
    }

    /**
     * @return mixed
     */
    private function getAppConfig(){
        return include(APP_PATH . '/config/console.php');
    }

    /**
     *
     */
    private function initGlobals(){
        $this->server = array_slice($_SERVER, 0);
    }

    /**
     *
     */
    protected function initDBConnections(){
        $DBS = $this->config('DBS');
        if(isset($DBS) && is_array($DBS)) {
            foreach($DBS as $key => $val) {
                foreach($val as $con => $prms) {
                    extract($prms);
                    /* @var $host
                     * @var $user
                     * @var $password
                     * @var $db
                     */
                    $db_connection = new DBConnection($host, $user, $password);
                    $this->{$key}[$con] = [
                        'connection' => $db_connection,
                        'db' => $db
                    ];
                    foreach($db as $key => $db_name) $this->db[$key] = [$db_name, $db_connection];
                }
            }
        } else {
            new Exception(
                'Application is not configured...'
            );
        }
    }

    /**
     *
     */
    protected function init(){
        $this->initConfig();
        $this->initDBConnections();
        $this->initGlobals();
    }

    /**
     * @param $name
     * @return null
     * @throws \ReflectionException
     */
    function __get($name){
        if(property_exists($this, $name)) {
            return $this->$name;
        } else {
            new Exception(
                strtr('Member "{member}" not exists in "{class}"',
                    [
                        "{member}" => $name,
                        "{class}" => (new ReflectionClass($this))->getShortName()
                    ]
                )
            );
        }

        return null;
    }

    /**
     * @param $name
     * @param $arguments
     * @return bool
     * @throws \ReflectionException
     */
    public function __call($name, $arguments){
        $direct_set = strpos($name, 'set') !== false;
        $name = strtolower(str_replace(['set', ' '], '', $name));
        if(property_exists($this, $name)) {
            $class = (new ReflectionClass($this))->getShortName();
            if(is_array($this->{$name}) && !$direct_set) {
                $getProperty = new ReflectionMethod($class, 'getArrayProperty');
                $setProperty = new ReflectionMethod($class, 'setArrayProperty');
                array_unshift($arguments, $name);
                switch(count($arguments)) {
                    case $getProperty->getNumberOfParameters():
                        return $getProperty->invokeArgs($this, $arguments);
                    case $setProperty->getNumberOfParameters():
                        return $setProperty->invokeArgs($this, $arguments);
                    default:
                        $getProperty = new ReflectionMethod($class, 'getProperty');

                        return $getProperty->invokeArgs($this, $arguments);
                }
            } else {
                if(method_exists($this, 'set' . $name)) {
                    call_user_func_array([$this, 'set' . $name], $arguments);
                } else {
                    $getProperty = new ReflectionMethod($class, 'getProperty');
                    $setProperty = new ReflectionMethod($class, 'setProperty');
                    array_unshift($arguments, $name);
                    switch(count($arguments)) {
                        case $getProperty->getNumberOfParameters():
                            return $getProperty->invokeArgs($this, $arguments);
                        case $setProperty->getNumberOfParameters():
                            return $setProperty->invokeArgs($this, $arguments);
                        default:
                            return $getProperty->invoke($this);
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param $property
     * @param $key
     * @return null
     */
    public function getArrayProperty($property, $key){
        if(isset($this->{$property}[$key])) return $this->{$property}[$key];

        return null;
    }

    /**
     * @param $property
     * @return null
     */
    public function getProperty($property){
        if(isset($this->{$property})) return $this->{$property};

        return null;
    }

    /**
     * @param $property
     * @param $value
     */
    public function setProperty($property, $value){
        $this->{$property} = $value;
    }

    /**
     * @param $property
     * @param $key
     * @param $value
     */
    public function setArrayProperty($property, $key, $value){
        if(is_null($value)) unset($this->{$property}[$key]);
        else $this->{$property}[$key] = $value;
    }

    /**
     * @param $name
     * @throws \Exception
     */
    public function SelectDB($name){
        if(isset($this->db[$name]) && is_array($this->db[$name])) {
            /**
             * @var DBConnection $connector
             */
            $connector = $this->db[$name][1];
            if(!$connector->initConnection($this->db[$name][0])) {
                throw new SelectDBException(
                    strtr('Data Base  "{db}" do not select: {reason}',
                        [
                            "{db}" => $name,
                            '{reason}' => $this->db[$name][1]->get_error()
                        ]
                    )
                );
            } else {
                if($this->db[$name][1]->get_errno() > 0) {
                    throw new SelectDBException($this->db[$name][1]->get_error());
                }
            }
        } else {
            throw new Exception(strtr('Data Base "{db}" not present in Application', ["{db}" => $name]));
        }
    }

    /**
     * @param $name
     * @return null
     */
    public function getDBConnection($name){
        if(isset($this->db[$name]) && is_array($this->db[$name])) {
            return $this->db[$name][1];
        } else {
            new Exception(
                strtr('Data Base configuration "{db}" not present in Application',
                    [
                        "{db}" => $name
                    ]
                )
            );
        }

        return null;
    }

}
