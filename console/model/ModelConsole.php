<?php

namespace sn\core\console\model;

use sn\core\exceptions\QueryException;
use sn\core\model\ModelBase;

/**
 * Class ModelConsole
 * @package sn\core\console\model
 */
class ModelConsole extends ModelBase{

    /**
     * @param $filter
     * @param null $prms
     * @return string
     */
    public static function BuildWhere(&$filter, &$prms = null){
        $result_where = "";
        $fields = !empty($filter['fields']) ? $filter['fields'] : [];
        foreach($fields as $field => $condition) {
            if(is_array($condition)) {
                $clause = $field . " ";
                $clause .= $condition['condition'] . " ";
                if(is_null($condition["value"]) || (strtolower($condition["value"]) == 'null')) {
                    $clause .= ($condition['not'] ? "not " : "") . "null";
                    $condition['not'] = false;
                } elseif($condition["condition"] == 'in') {
                    if(is_array($condition["value"])) {
                        $clause .= "(" . implode(', ', array_walk($condition["value"],
                                function(&$value){
                                    $value = "'" . static::PrepareForSql($value) . "'";
                                })) . ")";
                    } else {
                        $clause .= "(" . "'" . static::PrepareForSql($condition["value"]) . "'" . ")";
                    }
                } else {
                    $clause .= "'" . static::PrepareForSql($condition["value"]) . "'";
                }
                $clause = ($condition['not'] ? "not (" . $clause . ")" : $clause);
            } else {
                $clause = $field . " ";
                $clause .= (is_null($condition) ? "is" : "=") . " ";
                $clause .= (is_null($condition) ? "null" : "'" . static::PrepareForSql($condition["value"]) . "'");
            }
            $result[] = $clause;
        }
        if(!empty($result) && (count($result) > 0)) {
            $result_where = implode(" AND ", $result);
            $result_where = (!empty($result_where) ? " WHERE " . $result_where : '');
        }

        return $result_where;
    }

    /**
     * @param null $filter
     * @return int|null
     * @throws \Exception
     */
    public static function getTotalCount($filter = null){
        $query = "SELECT COUNT(*) FROM " . static::$table;
        if(!empty($filter)) $query .= static::BuildWhere($filter, $prms);
        if($result = static::Query($query, $prms)) {
            $response = static::FetchValue($result);
            static::FreeResult($result);
        } else {
            throw new QueryException(static::Error());
        }

        return $response;
    }

    /**
     * @param $filter
     * @return null
     * @throws \Exception
     */
    public static function getOne($filter){
        $data = null;
        if(!empty($filter)) {
            $query = "SELECT * FROM " . static::$table;
            $query .= static::BuildWhere($filter, $prms);
            $query .= static::BuildOrder($sort);
            $result = static::Query($query, $prms);
            if($result) {
                $data = static::FetchAssoc($result);
                static::FreeResult($result);
            } else {
                throw new QueryException(static::Error());
            }
        }

        return $data;
    }

    /**
     * @param $start
     * @param $limit
     * @param $res_count_rows
     * @param null $filter
     * @param null $sort
     * @return array|null
     * @throws \Exception
     */
    public static function getList($start, $limit, &$res_count_rows, &$filter = null, &$sort = null){
        $response = [];
        $query = "SELECT * FROM " . static::$table;
        $query .= static::BuildWhere($filter, $prms);
        $query .= static::BuildOrder($sort);
        if($limit != 0) $query .= " LIMIT $start, $limit";
        if($result = static::Query($query, $prms)) {
            $res_count_rows = static::getNumRows($result);
            while($row = static::FetchAssoc($result)) {
                $response[] = $row;
            }
            static::FreeResult($result);
        } else {
            throw new QueryException(static::Error());
        }

        return $response;
    }

    /**
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public static function Save($data){
        $prms = [];
        $result = false;
        if(!empty($data)) {
            $query = "REPLACE INTO " . static::$table;
            $fields = '';
            $values = '';
            foreach($data as $field => $value) {
                $fields .= (strlen($fields) ? ", " : "") . "`" . $field . "`";
                $values .= (strlen($values) ? ", " : "") . "'" . static::PrepareForSql($value) . "'";
            }
            $query .= "(" . $fields . ") VALUES(" . $values . ")";
            if(!($result = static::Query($query, $prms))) {
                throw new QueryException(static::error());
            }
            static::FreeResult($result);
            $result = true;
        }

        return $result;
    }

    /**
     * @param $where
     * @throws \Exception
     */
    public static function Delete($where){
        $query = "DELETE FROM " . static::$table;
        $query .= static::BuildWhere($where, $prms);
        if(!($result = static::Query($query, $prms))) {
            throw new QueryException(static::Error());
        }
        static::FreeResult($result);
    }

}