<?php

namespace app\core\console\controller;

use app\core\App;
use ReflectionClass;

abstract class ControllerBase{

    protected $layouts;
    protected $controller;
    protected $model;

    public $vars = [];

    /**
     * ControllerBase constructor.
     * @throws \ReflectionException
     */
    public function __construct(){
        $class = (new ReflectionClass($this))->getShortName();
        $this->controller = strtolower(str_replace('Controller', '', $class));
        $this->model = App::$modelsNS . '\Model' . ucfirst($this->controller);
    }

    /**
     * @param $url
     */
    public function redirect($url){
        App::$app->router()->redirect($url);
    }
}
