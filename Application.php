<?php

namespace sn\core;

/**
 * Class Application
 * @package core
 *
 * @property Router router
 * @property KeyStorage keystorage
 *
 * @inheritdoc
 * @method KeyStorage keyStorage()
 */
class Application extends Core{

    /**
     * @var KeyStorage
     */
    protected $keystorage;

    /**
     * @throws \Exception
     */
    protected function Init(){
        parent::Init();
        $this->registerHandlers();

        $this->SelectDB('default');
        $this->router = new Router($this);
        $this->keystorage = new KeyStorage();
        $this->keystorage->Init();
        $this->router->Init();
    }

    /**
     * @throws \Exception
     */
    public function Run(){
        $this->router->Handle();
    }

}