<?php

namespace app\core\console;

use app\core\exceptions\CommandArgsException;
use app\core\exceptions\ControllerPathException;
use Exception;
use ReflectionClass;

/**
 * Class Router
 */
class Router{

    /**
     * @var null
     */
    private $app = null;
    /**
     * @var
     */
    private $path;
    /**
     * @var
     */
    public $route;
    /**
     * @var
     */
    public $controller;
    /**
     * @var
     */
    public $action;
    /**
     * @var array
     */
    public $args = [];

    /**
     * Router constructor.
     * @param null $app
     */
    public function __construct($app = null){
        if(isset($app)) $this->app = $app;
    }

    /**
     *
     */
    private function parse_argv(){
        $argv = Console::$app->server('argv');
        if(is_array($argv) && (count($argv) > 1)) {
            array_shift($argv);
            $this->route = array_shift($argv);
            $this->route_explode_parts($this->route, $controller, $action);
            $this->action = $action;
            $this->controller = $controller;
            $this->args = $argv;
        }
    }

    /**
     * @param $path
     * @throws \app\core\exceptions\ControllerPathException
     */
    private function setPath($path){
        $path = rtrim($path, '/\\');
        $path .= DS;

        if(is_dir($path) == false) {
            throw new ControllerPathException('Invalid controller path: ' . $path . '');
        }
        $this->path = $path;
    }

    /**
     * @param $route
     * @param $controller
     * @param $action
     */
    private function route_explode_parts($route, &$controller, &$action){
        $parts = explode('/', $route);
        $cmd_path = $this->path;
        foreach($parts as $part) {
            if(is_dir($cmd_path . $part)) {
                $cmd_path .= $part . DS;
                array_shift($parts);
                continue;
            }
            if(is_file($cmd_path . 'Controller' . ucfirst($part) . '.php')) {
                $controller = $part;
                array_shift($parts);
                break;
            }
        }
        if(empty($controller)) $controller = 'index';
        $action = array_shift($parts);
        if(empty($action)) $action = $controller;
    }

    /**
     * @throws \Exception
     */
    public function init(){
        $this->setPath(APP_PATH . DS . 'console' . DS . 'controllers' . DS);
        $this->parse_argv();
    }

    /**
     *
     */
    public function handle(){

        $file = null;
        try {
            if(empty($this->controller) || empty($this->action)) {
                throw new CommandArgsException('Command line error');
            }
            $class = Console::$controllersNS . '\Controller' . ucfirst($this->controller);

            $controller = new $class();
            $call = null;
            $reflection = new ReflectionClass($controller);
            if($reflection->hasMethod($this->action)) {
                if(boolval(preg_match('#(@console)#i', $reflection->getMethod($this->action)->getDocComment(), $export)))
                    if(is_callable([$controller, $this->action])) $call = $this->action;
            } elseif(($this->controller == $this->action) && $reflection->hasMethod('index')) {
                if(boolval(preg_match('#(@console)#i', $reflection->getMethod('index')->getDocComment(), $export)))
                    if(is_callable([$controller, 'index'])) $call = 'index';
            }
            if(is_callable([$controller, $call]) && isset($this->app)) call_user_func([$controller, $call]);
        } catch(Exception $e) {
            fwrite(STDOUT, $e->getMessage());
        } finally {
            if(!empty($class)) {
                unset($class);
            }
        }
    }

    /**
     * @param $path
     * @param null $params
     * @param null $to_sef
     * @param null $sef_exclude_params
     * @param bool $canonical
     * @param bool $no_ctrl_ignore
     * @return mixed
     */
    public function UrlTo($path, $params = null, $to_sef = null, $sef_exclude_params = null, $canonical = false, $no_ctrl_ignore = false){
        return $path;
    }

}
