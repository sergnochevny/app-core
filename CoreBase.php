<?php

namespace sn\core;

use sn\core\exceptions\AppConfigException;
use sn\core\exceptions\ExitException;
use sn\core\exceptions\SelectDBException;
use Closure;
use Exception;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class CoreBase
 *
 * @method string server(...$prm)
 * @method string session(...$prm)
 * @method string router(...$prm)
 * @method string db(...$prm)
 * @method string connections($prm)
 * @method string config(...$prm)
 */
abstract class CoreBase{

    /**
     * @var Router
     */
    protected $router;
    /**
     * @var []
     */
    protected $server;

    /**
     * @var
     */
    protected $db;
    /**
     * @var array
     */
    protected $connections;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * Core constructor.
     * @param $app
     */
    public function __construct(&$app){
        $app = $this;
        $app->Init();
    }

    /**
     * @return mixed
     */
    abstract protected function getAppConfig();

    /**
     *
     */
    protected function initConfig(){
        $config = $this->getAppConfig();
        if(is_array($config)) {
            foreach($config as $key => $values) {
                if(function_exists($key)) {
                    if(is_array($values)) {
                        if(count(array_filter(array_keys($values), "is_int")) == count($values)) {
                            foreach($values as $value) {
                                if(!is_array($value)) {
                                    $value = [$value];
                                }
                                call_user_func_array($key, $value);
                            }
                        } else {
                            $closure = [];
                            foreach($values as $var => $val) {
                                if($val instanceof Closure) {
                                    $closure[$var] = $val;
                                } else {
                                    call_user_func_array($key, [$var, $val]);
                                }
                            }
                            foreach($closure as $var => $func) {
                                $val = call_user_func($func);
                                call_user_func_array($key, [$var, $val]);
                            }
                        }
                    } else {
                        call_user_func($key, $values);
                    }
                } else {
                    $this->config($key, $values);
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
            new AppConfigException(
                'Application is not configured...'
            );
        }
    }

    /**
     *
     */
    abstract protected function initGlobals();

    /**
     *
     */
    protected function Init(){
        $this->initConfig();
        $this->initDBConnections();
        $this->initGlobals();
    }

    /**
     * @param $exception
     */
    public function handleException($exception){
        $this->unregisterHandlers();
        if(!($exception instanceof ExitException)) {
            throw $exception;
        }

        if(!empty($this->config('errorHandler')) && is_string($this->config('errorHandler'))) {
            $classHandler = $this->config('errorHandler');
            $errorHandler = new $classHandler($exception);
            if($errorHandler instanceof ErrorHandlerInterface) {
                $errorHandler->Handle();
            }
        }
    }

    /**
     *
     */
    public function registerHandlers(){
        set_exception_handler([$this, 'handleException']);
    }

    /**
     *
     */
    public function unregisterHandlers(){
        restore_exception_handler();
    }

    /**
     * @param $name
     * @return null
     * @throws \ReflectionException
     */
    function __get($name){
        if(property_exists($this, $name)) {
            return $this->{$name};
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
     * @return bool|mixed
     * @throws \ReflectionException
     */
    public function __call($name, $arguments){
        $direct_set = strpos($name, 'set') !== false;
        $property_name = strtolower(str_replace(['set', ' '], '', $name));
        if(property_exists($this, $property_name)) {
            if(is_array($this->{$property_name}) && !$direct_set) {
                $getProperty = new ReflectionMethod($this, 'getArrayProperty');
                $setProperty = new ReflectionMethod($this, 'setArrayProperty');
                array_unshift($arguments, $property_name);
                switch(count($arguments)) {
                    case $getProperty->getNumberOfParameters():
                        return $getProperty->invokeArgs($this, $arguments);
                    case $setProperty->getNumberOfParameters():
                        return $setProperty->invokeArgs($this, $arguments);
                    default:
                        $getProperty = new ReflectionMethod($this, 'getProperty');

                        return $getProperty->invokeArgs($this, $arguments);
                }
            } else {
                if(method_exists($this, 'set' . $property_name)) {
                    call_user_func_array([$this, 'set' . $property_name], $arguments);
                } else {
                    $getProperty = new ReflectionMethod($this, 'getProperty');
                    $setProperty = new ReflectionMethod($this, 'setProperty');
                    array_unshift($arguments, $property_name);
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
                            '{reason}' => $this->db[$name][1]->getError()
                        ]
                    )
                );
            } else {
                if($this->db[$name][1]->getErrno() > 0) {
                    throw new SelectDBException($this->db[$name][1]->getError());
                }
            }
        } else {
            throw new SelectDBException(strtr('Data Base "{db}" not present in Application', ["{db}" => $name]));
        }
    }

    /**
     * @param $name
     * @return null|\sn\core\DBConnection
     */
    public function getDBConnection($name){
        if(isset($this->db[$name]) && is_array($this->db[$name])) {
            return $this->db[$name][1];
        } else {
            new Exception(strtr('Data Base configuration "{db}" not present in Application', ["{db}" => $name]));
        }

        return null;
    }

}
