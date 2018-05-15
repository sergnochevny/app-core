<?php

namespace sn\core\model;

use PDO;
use sn\core\App;
use sn\core\exceptions\BeginTransactionException;
use sn\core\exceptions\CommitTransactionException;
use sn\core\exceptions\ExecException;
use sn\core\exceptions\QueryException;
use sn\core\exceptions\RollBackTransactionException;

class ModelBase{

    protected static $inTransaction = false;

    protected static $connection = 'default';

    protected static $table;
    public static $filter_exclude_keys = ['scenario', 'reset'];

    /**
     * @param $prm
     * @param $var_tpl
     * @return string
     */
    protected static function BuildInSqlPrm($prm, $var_tpl){
        $in_values = array_fill(0, count($prm), ':' . $var_tpl);
        array_walk($in_values, function(&$val, $idx){
            $val = $val . $idx;
        });

        return implode(',', $in_values);
    }

    /**
     * @param $prm
     * @param $var_tpl
     * @return string
     */
    protected static function BuildFromToSqlPrm($prm_tpl, array $prm, $var_tpl){
        if(!empty($prm)) {
            $from_to_values_array = [];
            foreach($prm as $idx => $prm_item) {
                $from_to_values = '(' . $prm_tpl . ' >= :' . $var_tpl . 'from' . $idx;
                $from_to_values .= ' and ' . $prm_tpl . ' <  :' . $var_tpl . 'to' . $idx . ')';
                $from_to_values_array[] = $from_to_values;
            }

            return '(' . implode(' or ', $from_to_values_array) . ')';
        }

        return '';
    }

    /**
     * @param $sort
     * @return string
     */
    protected static function BuildOrder(&$sort){
        $order = '';
        if(isset($sort) && (count($sort) > 0)) {
            foreach($sort as $key => $val) {
                if(strlen($order) > 0) $order .= ',';
                $order .= ' ' . $key . ' ' . $val;
            }
            $order = ' ORDER BY ' . $order;
        }

        return $order;
    }

    /**
     * @param $filter
     * @param null $prms
     * @return string
     */
    public static function BuildWhere(&$filter, &$prms = null){
        $query = "";
        if(isset($filter)) {
            $where = "";
            foreach($filter as $key => $val) {
                if(!in_array($key, static::$filter_exclude_keys)) {
                    $where1 = "";
                    switch($val[0]) {
                        case 'like':
                            if(is_array($val[1])) {
                                foreach($val[1] as $idx => $like) {
                                    if(strlen($where1) > 0) $where1 .= ' or ';
                                    $where1 .= $key . " like :" . str_replace('.', '', $key) . $idx;
                                    $prms[str_replace('.', '', $key) . $idx] = '%' . $like . '%';
                                }
                            } else {
                                $where1 .= $key . " like :" . str_replace('.', '', $key);
                                $prms[str_replace('.', '', $key)] = '%' . $val[1] . '%';
                            }
                            break;
                        case '=':
                            if(is_array($val[1])) {
                                foreach($val[1] as $idx => $eq) {
                                    if(strlen($where1) > 0) $where1 .= ' or ';
                                    $where1 .= $key . " = :" . str_replace('.', '', $key) . $idx . "";
                                    $prms[str_replace('.', '', $key) . $idx] = $eq;
                                }
                            } else {
                                $where1 .= $key . " = :" . str_replace('.', '', $key);
                                $prms[str_replace('.', '', $key)] = $val[1];
                            }
                            break;
                        case 'between':
                            if(!empty($val[1]['from'])) {
                                $where1 = $key . " >= ':" . str_replace('.', '', $key) . "_from'";
                                $prms[str_replace('.', '', $key) . '_from'] = $val[1]['from'];
                            }
                            if(!empty($val[1]['to'])) {
                                if(strlen($where1) > 0) $where1 .= " and ";
                                $where1 .= $key . " < :" . str_replace('.', '', $key) . "_to";
                                $prms[str_replace('.', '', $key) . '_to'] = $val[1]['to'];
                            }
                            break;
                        case 'in':
                            if(is_array($val[1])) {
                                $result[] = $key . " in (" . static::BuildInSqlPrm($val[1], str_replace('.', '', $key)) . ")";
                                $prms[str_replace('.', '', $key)] = $val[1];
                            } else {
                                $where1 .= $key . " = :" . str_replace('.', '', $key);
                                $prms[str_replace('.', '', $key)] = $val[1];
                            }
                            break;
                    }

                    $where .= ((strlen($where1) > 0) ? ((strlen($where) > 0) ? " and (" : " (") . $where1 . ")" : '');
                }
            }
            if(strlen($where) > 0) {
                $query = " WHERE " . $where;
                $filter['active'] = true;
            }
        }

        return $query;
    }

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
     * @param $query
     * @param null $prms
     * @return mixed
     * @throws \sn\core\exceptions\QueryException
     */
    public static function Query($query, $prms = null){
        $res = App::$app->getDBConnection(static::$connection)->Query($query, $prms);

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
        $res = App::$app->getDBConnection(static::$connection)->Exec($query);

        if(!$res) {
            throw new ExecException(self::Error());
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
     * @return mixed
     */
    public static function LastId(){
        return App::$app->getDBConnection(static::$connection)->LastId();
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