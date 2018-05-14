<?php

namespace app\core\console;

use app\core\KeyStorage;

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
  protected function init(){
    parent::init();
    $this->SelectDB('default');
    $this->router = new Router($this);
    $this->keystorage = new KeyStorage();
    $this->keystorage->init();
    $this->router->init();
  }

  /**
   *
   * @throws \Exception
   */
  public function run(){
    $this->router->handle();
  }

}