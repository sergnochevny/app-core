<?php

namespace app\core;

use Exception;
use PDO;

/**
 * Class DBConnection
 * @package app\core
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
    public function query($query, $prms = null){
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
    public function exec($query){
        return $this->pdo->exec($query);
    }

    /**
     * @return mixed
     * @throws \PDOException
     */
    public function begin_transaction(){
        return $this->pdo->beginTransaction();
    }

    /**
     * @return mixed
     */
    public function commit(){
        return $this->pdo->commit();
    }

    /**
     * @return mixed
     */
    public function in_transaction(){
        return $this->pdo->inTransaction();
    }

    /**
     * @return mixed
     */
    public function roll_back(){
        return $this->pdo->rollBack();
    }

    /**
     * @return mixed
     */
    public function error(){
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
    public function last_id(){
        return $this->pdo->lastInsertId();
    }

    /**
     * @param $value
     * @return mixed
     */
    public function quote($value){
        return $this->pdo->quote($value);
    }

    /**
     * @return mixed
     */
    public function get_errno(){
        return $this->errno;
    }

    /**
     * @return mixed
     */
    public function get_error(){
        return $this->error;
    }

}