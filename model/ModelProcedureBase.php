<?php
/**
 * Copyright (c) 2018. AIT
 */

namespace sn\core\model;

use PDO;
use sn\core\App;
use sn\core\exceptions\BeginTransactionException;
use sn\core\exceptions\CommitTransactionException;
use sn\core\exceptions\QueryException;
use sn\core\exceptions\RollBackTransactionException;

class ModelProcedureBase{

    protected static $inTransaction = false;

    protected static $connection = 'default';

    protected static $procedure;

    /**
     * @param $query
     * @param null $prms
     * @return mixed
     * @throws \sn\core\exceptions\QueryException
     */
    protected static function Query($query, $prms = null){
        $res = App::$app->getDBConnection(static::$connection)->Query($query, $prms);

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
     * @param $text
     * @return mixed|null|string|string[]
     */
    public static function StripData($text){
        $quotes = ["\x27", "\x22", "\x60", "\t", "\n", "\r", "*", "%", "<", ">", "?", "!"];
        $goodquotes = ["-", "+", "#"];
        $repquotes = ["\-", "\+", "\#"];
        $text = trim(strip_tags($text));
        $text = str_replace($quotes, '', $text);
        $text = str_replace($goodquotes, $repquotes, $text);
        $text = preg_replace("/ +/i", " ", $text);

        return $text;
    }

    /**
     * @param $data
     * @return string
     */
    public static function Sanitize($data){
        if(is_string($data)) {
            if(function_exists('get_magic_quotes_gpc') == true && get_magic_quotes_gpc() == 1) {
                $data = stripslashes($data);
            }
            $data = nl2br(htmlspecialchars($data));
            $data = trim($data);
        }

        return $data;
    }

    /**
     * @return bool
     * @throws \sn\core\exceptions\BeginTransactionException
     * @throws \PDOException
     */
    public static function BeginTransaction(){
        if(!static::$inTransaction) {
            static::$inTransaction = App::$app->getDBConnection(static::$connection)->BeginTransaction();
            if(!static::$inTransaction) {
                throw new BeginTransactionException(self::Error());
            }
        }

        return static::$inTransaction;
    }

    /**
     * @return bool
     * @throws \sn\core\exceptions\CommitTransactionException
     */
    public static function Commit(){
        $res = !static::$inTransaction;
        if(static::$inTransaction) {
            $res = App::$app->getDBConnection(static::$connection)->Commit();
            if(!$res) {
                throw new CommitTransactionException(self::Error());
            }
            static::$inTransaction = false;
        }

        return $res;
    }

    /**
     * @return bool
     * @throws \sn\core\exceptions\RollBackTransactionException
     */
    public static function RollBack(){
        $res = !static::$inTransaction;
        if(static::$inTransaction) {
            $res = App::$app->getDBConnection(static::$connection)->RollBack();
            if(!$res) {
                throw new RollBackTransactionException(self::Error());
            }
            static::$inTransaction = false;
        }

        return $res;
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
        $res = App::$app->getDBConnection(static::$connection)->Query($query, $prms);

        if(!$res) {
            throw new QueryException(self::Error());
        }

        return $res;
    }

    /**
     * @param $str
     * @return mixed|null|string|string[]
     */
    public static function PrepareForSql($str){
        return static::StripData(static::Sanitize($str));
    }

    /**
     * @return mixed
     */
    public static function Error(){
        return App::$app->getDBConnection(static::$connection)->Error();
    }

    /**
     * @param \PDOStatement $from
     * @return null
     */
    public static function FetchAssoc($from){
        return $from ? $from->fetch(PDO::FETCH_ASSOC) : null;
    }

    /**
     * @param \PDOStatement $from
     * @return null
     */
    public static function FetchAssocAll($from){
        return $from ? $from->fetchAll(PDO::FETCH_ASSOC) : null;
    }

    /**
     * @param \PDOStatement $from
     * @param int $result_type
     * @return mixed
     */
    public static function FetchArray($from, $result_type = PDO::FETCH_BOTH){
        return $from ? $from->fetch($result_type) : null;
    }

    /**
     * @param \PDOStatement $from
     * @param int $result_type
     * @return mixed
     */
    public static function FetchArrayAll($from, $result_type = PDO::FETCH_BOTH){
        return $from ? $from->fetchAll($result_type) : null;
    }

    /**
     * @param \PDOStatement $from
     * @return mixed|null
     */
    public static function FetchValue($from){
        return $from ? $from->fetch(PDO::FETCH_COLUMN) : null;
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
     * @return int
     */
    public static function AffectedRows($from){
        return $from ? $from->rowCount() : 0;
    }

    /**
     * @param \PDOStatement $from
     */
    public static function FreeResult($from){
        if($from) $from->closeCursor();
    }
}