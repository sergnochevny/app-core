<?php

namespace sn\core;

use Exception;
use ReflectionClass;
use sn\core\exceptions\ExitException;
use sn\core\model\ModelRouter;

/**
 * Class Router
 * @package core
 */
class Router{

    /**
     * @var \sn\core\Application
     */
    private $app = null;

    private $path;
    private $exclude_params = ['page', 'back', 'idx'];
    /**
     * @var \sn\core\controller\ControllerBase|null
     */
    private $controllerObj;
    /**
     * @var string
     */
    public $base_url;
    /**
     * @var string
     */
    public $host;
    /**
     * @var string
     */
    public $route;
    /**
     * @var string
     */
    public $controller;
    /**
     * @var string
     */
    public $action;
    /**
     * @var array
     */
    public $args = [];

    /**
     * Router constructor.
     * @param null $app
     */
    public function __construct($app = null){
        if(isset($app)) $this->app = $app;
    }

    /**
     * @param $request_uri
     * @param $query_string
     * @param $query
     * @throws \Exception
     */
    private function parse_url(&$request_uri, &$query_string, &$query){
        $exploded_url = parse_url($query_string);
        if(!empty($exploded_url['query'])) {
            parse_str($exploded_url['query'], $query);
        } else {
            $query = [];
        }
        if(!empty($exploded_url['path'])) {
            parse_str($exploded_url['path'], $path);
        } else {
            $path = [];
        }
        $query = array_merge($query, $path);
        if($this->sef_enable()) {
            if(isset($query['route'])) {
                $query_sef_url = $query['route'];
                $query_path = str_replace('?', '&', $this->revert_sef_url($query_sef_url));
                $query_string = str_replace($query_sef_url, $query_path, $query_string);
                $request_uri = str_replace($query_sef_url, $query_path, $request_uri);
                parse_str($query_string, $query);
            }
        }
    }

    /**
     *
     * @throws \Exception
     */
    private function parse_request_url(){
        $query_string = App::$app->server('QUERY_STRING');
        $request_uri = App::$app->server('REQUEST_URI');
        $this->parse_url($request_uri, $query_string, $query);
        App::$app->server('QUERY_STRING', $query_string);
        App::$app->server('REQUEST_URI', $request_uri);
        App::$app->setGet($query);
        if(isset($query['redirect'])) {
            $redirect_url = $query['redirect'];
            unset($query['redirect']);
            $this->redirect_301($this->UrlTo($redirect_url, $query));
        }
        $this->route = $this->sanitize_url((empty(App::$app->get('route'))) ? '' : App::$app->get('route'));
        $this->url_explode_parts($this->route, $controller, $action, $args);
        $this->action = $action;
        $this->controller = $controller;
        $this->args = $args;
    }

    /**
     * @param $redirect_url
     */
    private function redirect_301($redirect_url){
        header('Location: ' . $redirect_url, true, 301);
        exit;
    }

    /**
     * @return bool
     */
    private function sef_enable(){
        $res = (!is_null(App::$app->keyStorage()->system_enable_sef) ? App::$app->keyStorage()->system_enable_sef : ENABLE_SEF) &&
            empty(App::$app->session('_a'));

        return $res;
    }

    /**
     * @param $sef_url
     * @param null $suff
     * @param null $pref
     * @return mixed
     * @throws \Exception
     */
    private function revert_sef_url($sef_url, $suff = null, $pref = null){
        $url = $sef_url;
        if(strlen(trim($pref))) {
            $pref = $this->convert($pref);
            if(strlen(trim($pref)))
                $sef_url = str_replace(trim($pref), '', $sef_url);
        }
        if(strlen(trim($suff))) {
            $suff = $this->convert($suff);
            if(strlen(trim($suff)))
                $sef_url = str_replace(trim($suff), '', $sef_url);
        }
        $sef_url = preg_replace('/-$/i', '', preg_replace('/^-/i', '', $sef_url));
        $url = ModelRouter::get_url($sef_url);

        return $url;
    }

    /**
     * @param $in
     * @return string
     */
    private function convert($in){
        $out = preg_replace('/[^a-zA-Z0-9]+/i', ' ', $in);
        $out = trim(preg_replace('/\s{2,}/', ' ', $out));
        $out = str_replace(' ', '-', $out);

        return strtolower($out);
    }

    /**
     * @param $path
     * @throws \Exception
     */
    private function setPath($path){
        $path = rtrim($path, '/\\');
        $path .= DS;

        if(is_dir($path) == false) {
            throw new Exception ('Invalid controller path: ' . $path . '');
        }
        $this->path = $path;
    }

    /**
     *
     */
    private function setBaseUrl(){
        $scheme = strtolower(trim(App::$app->server('REQUEST_SCHEME')));
        if(empty($scheme) && !empty(App::$app->server('HTTPS'))) $scheme = 'https';
        if(empty($scheme)) $this->base_url = strtolower(explode(DS, App::$app->server('SERVER_PROTOCOL'))[0]) . "://" . App::$app->server('SERVER_NAME') . (App::$app->server('SERVER_PORT') == '80' ? '' : ':' . App::$app->server('SERVER_PORT'));
        else $this->base_url = $scheme . "://" . App::$app->server('SERVER_NAME');
        $this->base_url = trim($this->base_url, '/\\');
        if(strlen(trim(dirname(App::$app->server('SCRIPT_NAME')), '/\\')))
            $this->base_url .= DS . trim(dirname(App::$app->server('SCRIPT_NAME')), '/\\');
        define('BASE_URL', $this->base_url);

        $parts = parse_url($this->base_url);
        $this->host = $parts['host'];
    }

    /**
     * @param $url
     * @param array $parts
     * @param null $flags
     * @param bool $new_url
     * @return string
     */
    private function http_build_url($url, $parts = [], $flags = null, &$new_url = false){
        if(!function_exists('http_build_url')) {

            if(is_null($flags))
                $flags = HTTP_URL_REPLACE;
            $keys = ['user', 'pass', 'port', 'path', 'query', 'fragment'];

            if($flags & HTTP_URL_STRIP_ALL) {
                $flags |= HTTP_URL_STRIP_USER;
                $flags |= HTTP_URL_STRIP_PASS;
                $flags |= HTTP_URL_STRIP_PORT;
                $flags |= HTTP_URL_STRIP_PATH;
                $flags |= HTTP_URL_STRIP_QUERY;
                $flags |= HTTP_URL_STRIP_FRAGMENT;
            } elseif($flags & HTTP_URL_STRIP_AUTH) {
                $flags |= HTTP_URL_STRIP_USER;
                $flags |= HTTP_URL_STRIP_PASS;
            }

            $parse_url = parse_url($url);
            if(isset($parts['scheme']))
                $parse_url['scheme'] = $parts['scheme'];
            if(isset($parts['host']))
                $parse_url['host'] = $parts['host'];
            if($flags & HTTP_URL_REPLACE) {
                foreach($keys as $key) {
                    if(isset($parts[$key]))
                        $parse_url[$key] = $parts[$key];
                }
            } else {
                if(isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
                    if(isset($parse_url['path']))
                        $parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/'); else
                        $parse_url['path'] = $parts['path'];
                }
                if(isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
                    if(isset($parse_url['query']))
                        $parse_url['query'] .= '&' . $parts['query']; else
                        $parse_url['query'] = $parts['query'];
                }
            }
            foreach($keys as $key) {
                if($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key)))
                    unset($parse_url[$key]);
            }
            $new_url = $parse_url;

            return ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '') . ((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') . '@' : '') . ((isset($parse_url['host'])) ? $parse_url['host'] : '') . ((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '') . ((isset($parse_url['path'])) ? $parse_url['path'] : '') . ((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '') . ((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '');
        } else {
            return http_build_url($url, $parts = [], $flags = HTTP_URL_REPLACE, $new_url);
        }
    }

    /**
     * @param $to_url
     * @param $url
     * @param null $suff
     * @param null $pref
     * @return string
     * @throws \Exception
     */
    private function build_sef_url($to_url, $url, $suff = null, $pref = null){
        $sef_url = $this->convert(trim($to_url));
        $url = ModelRouter::set_sef_url($sef_url, $url);
        if(strlen(trim($pref))) {
            $pref = $this->convert($pref);
            if(strlen(trim($pref)))
                $url = $pref . '-' . trim($url);
        }
        if(strlen(trim($suff))) {
            $suff = $this->convert($suff);
            if(strlen(trim($suff)))
                $url = $url . '-' . trim($suff);
        }
        $url = strtolower($url);

        return $url;
    }

    /**
     * @param $route
     * @return string
     */
    private function sanitize_url($route){
        if(empty($route)) $route = 'index';

        return trim($route, '/\\');
    }

    /**
     * @param $route
     * @param $controller
     * @param $action
     * @param $args
     */
    private function url_explode_parts($route, &$controller, &$action, &$args){
        $parts = explode('/', $route);
        $cmd_path = $this->path;
        foreach($parts as $part) {
            if(is_dir($cmd_path . $part)) {
                $cmd_path .= $part . DS;
                array_shift($parts);
                continue;
            }
            if(is_file($cmd_path . 'Controller' . ucfirst($part) . '.php')) {
                $controller = $part;
                array_shift($parts);
                break;
            }
        }
        if(empty($controller)) $controller = 'index';
        $action = array_shift($parts);
        if(empty($action)) $action = $controller;
        $args = $parts;
    }

    /**
     * @throws \Exception
     */
    public function init(){
        if(!function_exists('http_build_url')) {
            define('HTTP_URL_REPLACE', 1);                // Replace every part of the first URL when there's one of the second URL
            define('HTTP_URL_JOIN_PATH', 2);            // Join relative paths
            define('HTTP_URL_JOIN_QUERY', 4);            // Join query strings
            define('HTTP_URL_STRIP_USER', 8);            // Strip any user authentication information
            define('HTTP_URL_STRIP_PASS', 16);            // Strip any password authentication information
            define('HTTP_URL_STRIP_AUTH', 32);            // Strip any authentication information
            define('HTTP_URL_STRIP_PORT', 64);            // Strip explicit port numbers
            define('HTTP_URL_STRIP_PATH', 128);            // Strip complete path
            define('HTTP_URL_STRIP_QUERY', 256);        // Strip query string
            define('HTTP_URL_STRIP_FRAGMENT', 512);        // Strip any fragments (#identifier)
            define('HTTP_URL_STRIP_ALL', 1024);            // Strip anything but scheme and host
        }

        $this->setPath(APP_PATH . DS . 'controllers' . DS);
    }

    /**
     * @param $route
     * @param $controller
     * @param $action
     * @param $args
     * @throws \Exception
     */
    public function parse_referrer_url(&$route, &$controller, &$action, &$args){
        $referrer_url = App::$app->server('HTTP_REFERER');
        $referrer_uri = str_replace($this->base_url, '', $referrer_url);
        $query_string = 'route=' . ltrim($referrer_uri, "\\/");
        $exploded_url = parse_url($query_string);
        if(!empty($exploded_url['query'])) {
            parse_str($exploded_url['query'], $query);
        } else {
            $query = [];
        }
        if(!empty($exploded_url['path'])) {
            parse_str($exploded_url['path'], $path);
        } else {
            $path = [];
        }
        $query = array_merge($query, $path);
        $this->parse_url($referrer_uri, $query_string, $query);
        $route = $this->sanitize_url((empty($query['route'])) ? '' : $query['route']);
        $this->url_explode_parts($route, $controller, $action, $args);
    }

    /**
     * @throws \Exception
     */
    public function handle(){

        $this->setBaseUrl();
        $this->parse_request_url();

        $file = null;
        try {
            $class = App::$controllersNS . '\Controller' . ucfirst($this->controller);

            $this->controllerObj = new $class();
            $call = null;
            $reflection = new ReflectionClass($this->controllerObj);
            if($reflection->hasMethod($this->action)) {
                if(boolval(preg_match('#(@export)#i', $reflection->getMethod($this->action)->getDocComment(), $export)))
                    if(is_callable([$this->controllerObj, $this->action])) $call = $this->action;
            } elseif(($this->controller == $this->action) && $reflection->hasMethod('index')) {
                if(boolval(preg_match('#(@export)#i', $reflection->getMethod('index')->getDocComment(), $export)))
                    if(is_callable([$this->controllerObj, 'index'])) $call = 'index';
            }
            if(!isset($call)) {
                throw new ExitException();
            } else {
                if(is_callable([$this->controllerObj, 'scenario']) && isset($this->app))
                    call_user_func([$this->controllerObj, 'scenario'], $this->app->get('method'));
                call_user_func([$this->controllerObj, $call]);
            }
        } catch(Exception $e) {
            throw new ExitException($e->getMessage());
        } finally {
            if(!empty($class)) {
                unset($class);
            }
        }
    }

    /**
     * @param $url
     */
    public function redirect($url){
        if(App::$app->request_is_ajax()) {
            $redirect_script = '<script>' .
                'window.location="' . $url . '";' .
                'if(typeof $ !== "undefined") setTimeout(function(){$("body").waitloader("show");},50);' .
                '</script>';
            exit($redirect_script);
        }

        header('Location: ' . $url, true);
        exit();
    }

    /**
     * @param $path
     * @param null $params
     * @return string
     */
    public function RefTo($path, $params = null){
        if(!is_null($params) && is_array($params) && (count($params) > 0))
            $url = $this->http_build_url($path, ['query' => http_build_query($params)]);
        else $url = $this->http_build_url($path);

        return $url;
    }

    /**
     * @param $path
     * @param null $params
     * @param null $to_sef
     * @param null $sef_exclude_params
     * @param bool $canonical
     * @param bool $no_ctrl_ignore
     * @return string
     * @throws \Exception
     */
    public function UrlTo($path, $params = null, $to_sef = null, $sef_exclude_params = null, $canonical = false, $no_ctrl_ignore = false){
        if($this->sef_enable()) {
            $sef_exclude_params = isset($sef_exclude_params) ? $sef_exclude_params : [];
            if(!$no_ctrl_ignore && is_callable([App::$controllersNS . '\Controller' . ucfirst($this->controller), 'urlto_sef_ignore_prms']))
                $exclude_params = forward_static_call([App::$controllersNS . '\Controller' . ucfirst($this->controller), 'urlto_sef_ignore_prms']);
            if(!isset($exclude_params)) $exclude_params = [];
            else $exclude_params = isset($exclude_params[$this->action]) ? $exclude_params[$this->action] : [];
            $sef_exclude_params = array_merge($this->exclude_params, $sef_exclude_params, $exclude_params);
            $path = rtrim(trim($path), DS);
            if(strpos($path, '{base_url}') !== false) {
                $path = str_replace('{base_url}', $this->base_url, $path);
            }
            $_path = $path;
            if(strpos($_path, $this->base_url) !== false)
                $_path = trim(str_replace($this->base_url, '', $path), '/\\');
            if(preg_match('#(.*)\?(.*)#i', $_path, $matches))
                $_path = $matches[1];
            if(count($matches) > 2) {
                parse_str($matches[2], $_params);
                if(!(isset($params) && is_array($params))) $params = [];
                $params = array_merge($params, $_params);
            }
            $sef_include_params = (isset($params) && (count($params) > 0)) ? array_diff_key($params, array_flip($sef_exclude_params)) : null;
            if(isset($sef_include_params)) $_path = $this->http_build_url(trim($_path, DS), ['query' => http_build_query($sef_include_params)]);
            else $_path = $this->http_build_url(trim($_path, DS));
            if(isset($to_sef)) {
                $path = $this->build_sef_url($to_sef, $_path);
            } else {
                $path = ModelRouter::get_sef_url($_path);
            }
            $params = isset($params) ? array_intersect_key($params, array_flip($sef_exclude_params)) : [];
            if(strpos($path, $this->base_url) == false)
                $path = $this->base_url . DS . $path;
            if(!$canonical && !is_null($params) && is_array($params) && (count($params) > 0))
                $url = $this->http_build_url($path, ['query' => http_build_query($params)]);
            else $url = $this->http_build_url($path);
        } else {
            $path = rtrim(trim($path), DS);
            if(strpos($path, '{base_url}') !== false) {
                $path = str_replace('{base_url}', $this->base_url, $path);
            } else {
                if(strpos($path, $this->base_url) == false)
                    $path = $this->base_url . DS . $path;
            }

            if(!is_null($params))
                $url = $this->http_build_url($path, ['query' => http_build_query($params)]);
            else $url = $this->http_build_url($path);
        }

        return $url;
    }

    /**
     * @return \sn\core\controller\ControllerBase|null
     */
    public function get_controller(){
        return $this->controllerObj;
    }
}
