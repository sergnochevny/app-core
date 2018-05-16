<?php
/**
 * Copyright (c) 2018. AIT
 */

/**
 * Date: 16.05.2018
 * Time: 16:50
 */

namespace sn\core\model;

class AbstractModel{

    protected static $inTransaction = false;

    protected static $connection = 'default';

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
     * @param $str
     * @return mixed|null|string|string[]
     */
    public static function PrepareForSql($str){
        return static::StripData(static::Sanitize($str));
    }

}