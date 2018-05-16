<?php

namespace sn\core\console\controller;

use sn\core\App;
use ReflectionClass;

abstract class ControllerBase{

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

}
