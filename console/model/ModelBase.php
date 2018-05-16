<?php

namespace sn\core\console\model;

use PDO;
use sn\core\console\Console;
use sn\core\exceptions\BeginTransactionException;
use sn\core\exceptions\CommitTransactionException;
use sn\core\exceptions\RollBackTransactionException;
use sn\core\model\AbstractModel;

abstract class ModelBase extends AbstractModel{

    /**
     * @return bool
     * @throws \sn\core\exceptions\BeginTransactionException
     * @throws \PDOException
     */
    public static function BeginTransaction(){
        if(!static::$inTransaction) {
            static::$inTransaction = Console::$app->getDBConnection(static::$connection)->BeginTransaction();
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
            $res = Console::$app->getDBConnection(static::$connection)->Commit();
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
            $res = Console::$app->getDBConnection(static::$connection)->RollBack();
            if(!$res) {
                throw new RollBackTransactionException(self::Error());
            }
            static::$inTransaction = false;
        }

        return $res;
    }

    /**
     * @return mixed
     */
    public static function Error(){
        return Console::$app->getDBConnection(static::$connection)->Error();
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