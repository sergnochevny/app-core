<?php

namespace sn\core\console;

use sn\core\KeyStorage;

/**
 * Class Application
 *
 * @property Router router
 * @property KeyStorage keystorage
 *
 * @method string|[] server(...$prm)
 * @method string|[] session(...$prm)
 * @method string|[] router(...$prm)
 * @method string|[] db($prm)
 * @method string|[] connections($prm)
 * @method string|[] get(...$prm)
 * @method string|[] post(...$prm)
 * @method string|[] config(...$key)
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
    $this->SelectDB('default');
    $this->router = new Router($this);
    $this->keystorage = new KeyStorage();
    $this->keystorage->Init();
    $this->router->Init();
  }

  /**
   *
   * @throws \Exception
   */
  public function Run(){
    $this->router->Handle();
  }

}