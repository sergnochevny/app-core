<?php

namespace app\core\controller;

use app\core\App;
use app\core\View;
use ReflectionClass;

abstract class ControllerBase{

    /**
     * @var
     */
    protected $layouts;
    /**
     * @var string
     */
    public $controller;
    /**
     * @var string
     */
    public $model;
    /**
     * @var \app\core\View
     */
    public $view;
    /**
     * @var array
     */
    public $vars = [];

    /**
     * ControllerBase constructor.
     * @throws \ReflectionException
     */
    public function __construct(){
        $class = (new ReflectionClass(get_called_class()))->getShortName();
        $this->controller = strtolower(str_replace('Controller', '', $class));
        $this->view = new View($this->layouts, $this);
        $this->model = App::$modelsNS . '\Model' . ucfirst($this->controller);
    }

    /**
     * @param $url
     */
    public function redirect($url){
        App::$app->router()->redirect($url);
    }
}
