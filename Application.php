<?php

namespace sn\core;

/**
 * Class Application
 * @package sn\core
 *
 * @property Router router
 * @method string|[] server(...$prm)
 * @method string|[] session(...$prm)
 * @method string|[] router(...$prm)
 * @method string|[] db($prm)
 * @method string|[] connections($prm)
 * @method string|[] get(...$prm)
 * @method string|[] post(...$prm)
 * @method string|[] config(...$key)
 * @inheritdoc
 * @method KeyStorage keyStorage()
 */
class Application extends Core{

    /**
     * @var KeyStorage
     */
    protected $KeyStorage;

    /**
     * @throws \Exception
     */
    protected function Init(){
        parent::Init();
        $this->registerHandlers();

        $this->SelectDB('default');
        $this->router = new Router($this);
        $this->KeyStorage = new KeyStorage();
        $this->KeyStorage->Init();
        $this->router->Init();
    }

    /**
     * @throws \Exception
     */
    public function Run(){
        $this->router->Handle();
    }

}