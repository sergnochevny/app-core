<?php

namespace sn\core\console;

class Console{

    /* @var $app Application */
    static public $app;
    static public $classMap;

    static public $controllersNS = 'console\controllers';
    static public $modelsNS = 'console\models';

    static function autoload($className){
        if(!empty(static::$classMap[$className])) {
            $filename = static::$classMap[$className];
        } else {
            $filename = $className . '.php';
        }
        $absFilename = realpath(strtr(APP_PATH . DS . $filename, '\\', DS));
        return ($absFilename === false) ?  false : include($absFilename);
    }

    /**
     *
     */
    public static function set_autoloads(){
        static::$classMap = include(__DIR__ . '/classes.php');
        spl_autoload_register(['self', 'autoload']);
    }

    /**
     * @throws \Exception
     */
    public static function run(){
        static::set_autoloads();
        (new Application(self::$app))->run();
    }
}
