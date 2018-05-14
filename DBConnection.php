<?php

namespace sn\core;

use Exception;
use PDO;

/**
 * Class DBConnection
 * @package sn\core
 */
class DBConnection{

    /**
     * @var PDO
     */
    private $pdo;
    /**
     * @var \PDOStatement
     */
    private $statement;

    public $host;
    public $username;
    public $password;
    private $error;
    private $errno;

    /**
     * DBConnection constructor.
     * @param $host
     * @param $username
     * @param $password
     */
    function __construct($host, $username, $password){
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param $db_name
     * @return bool
     */
    public function initConnection($db_name){
        try {
            $this->pdo = new PDO('mysql:host=' . $this->host . ';dbname=' . $db_name, $this->username, $this->password);

            return true;
        } catch(Exception $e) {
            $this->errno = $e->getCode();
            $this->error = $e->getMessage();
        }

        return false;
    }

    /**
     * @param $query
     * @param null $prms
     * @return \PDOStatement|bool
     */
    public function Query($query, $prms = null){
        $this->statement = $this->pdo->prepare($query);
        if(!empty($prms) && is_array($prms)) {
            foreach($prms as $key => $values) {
                if(is_array($values)) {
                    foreach($values as $idx => $value) {
                        if (is_array($value)){
                            foreach($value as $v_idx=>$v_value){
                                $this->statement->bindValue(':' . $key . $v_idx . $idx, $v_value);
                            }
                        } else {
                            $this->statement->bindValue(':' . $key . $idx, $value);
                        }
                    }
                } else {
                    $this->statement->bindValue(':' . $key, $values);
                }
            }
        }

        return $this->statement->execute() ? $this->statement : false;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function Exec($query){
        return $this->pdo->exec($query);
    }

    /**
     * @return mixed
     * @throws \PDOException
     */
    public function BeginTransaction(){
        return $this->pdo->beginTransaction();
    }

    /**
     * @return mixed
     */
    public function Commit(){
        return $this->pdo->commit();
    }

    /**
     * @return mixed
     */
    public function InTransaction(){
        return $this->pdo->inTransaction();
    }

    /**
     * @return mixed
     */
    public function RollBack(){
        return $this->pdo->rollBack();
    }

    /**
     * @return mixed
     */
    public function Error(){
        $this->error = $this->pdo->errorInfo();
        if(!empty($this->statement)) {
            $this->error = $this->statement->errorInfo();
            if(!empty($this->error)) {
                $this->errno = $this->error[0];
                $this->error = !empty($this->error[2]) ? $this->error[2] : "\r" . $this->statement->queryString;
            } elseif(!empty($this->error)) {
                $this->errno = $this->error[0];
                $this->error = !empty($this->error[2]) ? $this->error[2] : '';
            }
        }

        return 'SQL ERROR ' . $this->errno . ': ' . $this->error;
    }

    /**
     * @return mixed
     */
    public function LastId(){
        return $this->pdo->lastInsertId();
    }

    /**
     * @param $value
     * @return mixed
     */
    public function Quote($value){
        return $this->pdo->quote($value);
    }

    /**
     * @return mixed
     */
    public function getErrno(){
        return $this->errno;
    }

    /**
     * @return mixed
     */
    public function getError(){
        return $this->error;
    }

}