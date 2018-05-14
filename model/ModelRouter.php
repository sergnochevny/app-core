<?php

namespace sn\core\model;

/**
 * Class ModelRouter
 * @package models
 */
class ModelRouter extends ModelBase{

    /**
     * @param $sef_url
     * @param $url
     * @return string
     * @throws \sn\core\exceptions\QueryException
     */
    public static function setSefUrl($sef_url, $url){
        $_sef_url = $sef_url;
        $iterator = 0;
        while(true) {
            $sql = "SELECT * FROM url_sef WHERE sef = :sef_url";
            $find_result = static::Query($sql, ['sef_url' => $sef_url]);
            if(!($res = static::FetchAssoc($find_result))) {
                $sql = "REPLACE INTO url_sef(url,sef) VALUES(:url, :sef_url)";
                $res = static::Query($sql, ['url' => $url, 'sef_url' => $sef_url]);
                if(!$res) $sef_url = $url;
                break;
            } else {
                if($res['url'] !== $url) {
                    $iterator += 1;
                    $sef_url = $_sef_url . '-' . $iterator;
                } else break;
            }
        }

        return $sef_url;
    }

    /**
     * @param $url
     * @return mixed
     * @throws \sn\core\exceptions\QueryException
     */
    public static function getSefUrl($url){
        $sef_url = $url;
        if(!empty($sef_url)) {
            $sql = "SELECT * FROM url_sef WHERE url = :url";
            $prms = ['url' => $url];
            $find_result = static::Query($sql, $prms);
            if($find_result && static::getNumRows($find_result)) {
                $res = static::FetchAssoc($find_result);
                $sef_url = $res['sef'];
            }
        }

        return $sef_url;
    }

    /**
     * @param $sef_url
     * @return mixed
     * @throws \sn\core\exceptions\QueryException
     */
    public static function getUrl($sef_url){
        $url = $sef_url;
        if($sef_url != '') {
            $sql = 'SELECT * FROM url_sef WHERE sef = :sef_url';
            $find_result = static::Query($sql, ['sef_url' => $sef_url]);
            if(static::getNumRows($find_result)) {
                $res = static::FetchAssoc($find_result);
                $url = $res['url'];
            }
        }

        return $url;
    }

}