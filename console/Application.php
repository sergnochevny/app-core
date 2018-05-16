<?php

namespace sn\core\console;

/**
 * Class Application
 *
 * @property Router router
 * @method KeyStorage keyStorage()
 * @method string|[] server(...$prm)
 * @method string|[] router(...$prm)
 * @method string|[] db($prm)
 * @method string|[] connections($prm)
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