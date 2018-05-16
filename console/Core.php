<?php

namespace sn\core\console;

use sn\core\CoreBase;

/**
 * class Core
 *
 * @method string server($prm)
 * @method string session($prm)
 * @method string router($prm)
 * @method string db($prm)
 * @method string connections($prm)
 * @method string config(...$prm)
 */
class Core extends CoreBase{

    /**
     *
     */
    protected function initGlobals(){
        $this->server = array_slice($_SERVER, 0);
    }

    /**
     * @return mixed
     */
    protected function getAppConfig(){
        return include(APP_PATH . '/config/console.php');
    }

}
