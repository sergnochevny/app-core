<?php
/**
 * Copyright (c) 2018. AIT
 */

namespace sn\core\model;

use sn\core\console\Console;
use sn\core\console\model\ModelBase;
use sn\core\exceptions\QueryException;

class ModelProcedureBase extends ModelBase{

    protected static $procedure;

    /**
     * @param $query
     * @param null $prms
     * @return mixed
     * @throws \sn\core\exceptions\QueryException
     */
    protected static function Query($query, $prms = null){
        $res = Console::$app->getDBConnection(static::$connection)->Query($query, $prms);

        if(!$res) {
            throw new QueryException(self::Error());
        }

        return $res;
    }

    /**
     * @throws \Exception
     */
    protected static function getListPreparedProcPrm(){
        $listProcPrm = static::getParameters();
        $parameters = [];
        foreach($listProcPrm as $prm => $type) {
            $parameters[] = ':' . $prm;
        }

        return $parameters;
    }

    /**
     * @return null
     * @throws \Exception
     */
    public static function getParameters(){
        $response = null;
        $query = "SELECT PARAMETER_NAME, DATA_TYPE";
        $query .= " FROM information_schema.parameters";
        $query .= " WHERE SPECIFIC_NAME = :procedure_name";
        $result = static::Query($query, ['procedure_name' => static::$procedure]);
        if($result) {
            while($row = static::FetchAssoc($result)) {
                $response[$row['PARAMETER_NAME']] = $row['DATA_TYPE'];
            }
        }

        return $response;
    }

    /**
     * @param null $prms
     * @return mixed
     * @throws \sn\core\exceptions\QueryException
     * @throws \Exception
     */
    public static function Execute($prms = null){
        $parameters = implode(',', static::getListPreparedProcPrm());
        if(empty($parameters)) {
            $parameters = '';
        }
        $query = "CALL " . static::$procedure . "(" . $parameters . ")";
        $res = Console::$app->getDBConnection(static::$connection)->Query($query, $prms);

        if(!$res) {
            throw new QueryException(self::Error());
        }

        return $res;
    }

}