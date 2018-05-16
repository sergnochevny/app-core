<?php

namespace sn\core;

/**
 * Class Core
 *
 * @method string|[] server(...$prm)
 * @method string|[] session(...$prm)
 * @method string|[]|Router router(...$prm)
 * @method string|[] db(...$prm)
 * @method string|[] post(...$prm)
 * @method string|[] get(...$prm)
 * @method string|[] cookie(...$prm)
 * @method string|[] connections(...$prm)
 * @method string|[] config(...$prm)
 *
 */
class Core extends CoreBase{

    /**
     * @var []
     */
    protected $session;
    /**
     * @var []
     */
    protected $post;
    /**
     * @var []
     */
    protected $get;
    /**
     * @var []
     */
    protected $cookie;
    /**
     * @var []
     */
    protected $request;
    /**
     * @var
     */
    protected $mailer;

    /**
     * @return mixed
     */
    protected function getAppConfig(){
        return include(APP_PATH . '/config/web.php');
    }

    /**
     *
     */
    protected function initGlobals(){
        $this->post = array_slice($_POST, 0);
        $this->get = array_slice($_GET, 0);
        $this->server = array_slice($_SERVER, 0);
        $this->cookie = array_slice($_COOKIE, 0);
        $this->request = array_slice($_REQUEST, 0);
    }

    /**
     *
     */
    protected function initSession(){
        if(!is_null($this->get('pay_notify'))) {
            $s_id = $this->get('pay_notify');
            session_id($s_id);
        }
        session_start();
        $this->session = array_filter($_SESSION);
    }

    /**
     *
     */
    protected function Init(){
        parent::Init();
        $this->initSession();
    }

    /**
     * @param $key
     * @param $value
     */
    public function setSession($key, $value){
        $this->setArrayProperty('session', $key, $value);
        if(is_null($value)) {
            unset($_SESSION[$key]);
        } else {
            $_SESSION[$key] = $value;
        }
    }
    /**
     * @param $key
     * @param $value
     */
    public function setCookie($key, $value){
        $this->setSession($key, $value);
        if(is_null($value)) {
            unset($_COOKIE[$key]);
            setcookie($key, '', 0, '', App::$app->server('SERVER_NAME'));
        } else {
            $_COOKIE[$key] = $value;
            setcookie($key, $value, 0, '', App::$app->server('SERVER_NAME'));
        }
    }

    /**
     * @return bool
     */
    public function RequestIsAjax(){
        return !empty($this->server('HTTP_X_REQUESTED_WITH')) && strtolower($this->server('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest';
    }

    /**
     * @return bool
     */
    public function RequestIsPost(){
        return $this->server('REQUEST_METHOD') == 'POST';
    }

    /**
     * @return bool
     */
    public function RequestIsGet(){
        return $this->server('REQUEST_METHOD') == 'GET';
    }

}
