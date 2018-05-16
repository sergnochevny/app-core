<?php
/**
 * Copyright (c) 2018. AIT
 */

namespace sn\core\console\model;

use sn\core\console\Console;
use sn\core\exceptions\ExecException;
use sn\core\exceptions\QueryException;

class ModelTableBase extends ModelBase{

    protected static $table;

    public static $filter_exclude_keys = ['scenario', 'reset'];

    /**
     * @return null
     * @throws \Exception
     */
    public static function getFields(){
        $response = null;
        $query = "DESCRIBE " . static::$table;
        $result = static::Query($query);
        if($result) {
            while($row = static::FetchAssoc($result)) {
                $response[$row['Field']] = $row;
            }
        }

        return $response;
    }

    /**
     * @param $query
     * @param null $prms
     * @return mixed
     * @throws \sn\core\exceptions\QueryException
     */
    public static function Query($query, $prms = null){
        $res = Console::$app->getDBConnection(static::$connection)->Query($query, $prms);

        if(!$res) {
            throw new QueryException(self::Error());
        }

        return $res;
    }

    /**
     * @param $query
     * @return mixed
     * @throws \sn\core\exceptions\ExecException
     */
    public static function Exec($query){
        $res = Console::$app->getDBConnection(static::$connection)->Exec($query);

        if(!$res) {
            throw new ExecException(self::Error());
        }

        return $res;
    }

    /**
     * @return mixed
     */
    public static function LastId(){
        return Console::$app->getDBConnection(static::$connection)->LastId();
    }

    /**
     * @param \PDOStatement $from
     * @return int
     */
    public static function getNumRows($from){
        return $from ? $from->rowCount() : 0;
    }

    /**
     * @param \PDOStatement $from
     */
    public static function FreeResult($from){
        if($from) $from->closeCursor();
    }
}