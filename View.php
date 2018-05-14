<?php

namespace sn\core;

use sn\core\controller\ControllerBase;
use Exception;

/**
 * Class View
 * @package sn\core
 */
class View{

    private $layouts;
    private $vars = [];
    private $meta = null;
    private $js = [];
    private $jsFiles = [];
    private $cssFiles = [];
    protected $forcedJsFiles = [];

    /**
     * @var \sn\core\controller\ControllerBase
     */
    public $controller;

    /**
     * Template constructor.
     * @param $layouts
     * @param \sn\core\controller\ControllerBase $controller
     */
    public function __construct($layouts, ControllerBase $controller){
        $this->layouts = $layouts;
        $this->controller = $controller;
    }

    /**
     * @param $lines
     */
    protected function RenderJs(&$lines){
        if(!empty(array_filter($this->js))) {
            $lines[] = implode("\n", array_filter($this->js));
        }
    }

    /**
     * @throws \Exception
     */
    public function RenderCssLinks(){
        $lines = [];
        if(!empty($this->cssFiles)) {
            ksort($this->cssFiles, SORT_NUMERIC);
            foreach($this->cssFiles as $cssFiles) {
                $cssFiles = array_filter(array_unique($cssFiles));
                foreach($cssFiles as $cssFile) {
                    $lines[] = '<link rel="stylesheet" type="text/css" href="' . $cssFile . '">';
                }
            }
        }

        return empty($lines) ? '' : preg_replace("/ {2,}/", " ", strtr(implode("\n", $lines), ["\n" => '', "\r" => '']));
    }

    public function RenderJsLinks(){
        $lines = [];
        $js = array_filter($this->jsFiles);
        if(!empty($js)) {
            if(empty($lines)) $lines = ["(function(){"];
            $lines[] = "Array.isArray||(Array.isArray=function(b){return'[object Array]'===Object.prototype.toString.call(b)}),window.fn||(window.fn=function(b,c,d){for(var t,e=function(w){for(var x=0;x<c.length;x++){var y=c[x],z=new RegExp('^'+f(y).split('\\*').join('.*')+'$').test(w);if(!0===z)return!0}return!1},f=function(w){return w.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,'\\$&')},g=function(w){return!!document.querySelectorAll('script[src=\''+w+'\']').length},h=function(w){return function(){1>--u&&d()}},s=function(w){if((!g(w))||e(w)){var x=document.createElement('script');x.setAttribute('src',w),x.onload=h(w),document.body.appendChild(x)}else h(w)()},u=b.length;0<b.length&&(t=b.shift());)if(Array.isArray(t)){var v=b.splice(0,b.length);u-=v.length,window.fn(t,c,function(){0<v.length?window.fn(v,c,d):d()})}else s(t)});";
            $lines[] = "window.fn([";
            $scripts = [];
            ksort($js, SORT_NUMERIC);
            foreach($js as $jsFiles) {
                $jsFiles = array_filter(array_unique($jsFiles));
                if(!empty($jsFiles)) {
                    $scripts[] = "['" . implode("','", $jsFiles) . "']";
                }
            }
            $lines[] = implode(",", $scripts) . "],[";
            $scripts = '';
            if(!empty($this->forcedJsFiles)) {
                $scripts = "'" . implode("','", array_filter(array_unique($this->forcedJsFiles))) . "'";
            }
            $lines[] = $scripts . "], function() {";
            $this->RenderJs($lines);
            $lines[] = '});';
        }
        if(!empty($lines)) $lines[] = '})();';

        return (empty($lines) ? '' : preg_replace("/ {2,}/", " ", strtr("<script type = 'text/javascript'>" . implode("\n", $lines) . "</script>", ["\n" => '', "\r" => ''])));
    }

    /**
     * @param $js
     */
    public function RegisterJS($js){
        $this->js[] = $js;
    }

    /**
     * @param $href_js
     * @param int $level
     * @param bool $forcedLoading
     */
    public function RegisterJSFile($href_js, $level = 0, $forcedLoading = false){
        $this->jsFiles[$level][] = $href_js;
        if($forcedLoading) $this->forcedJsFiles[] = $href_js;
    }

    /**
     * @param $href_css
     */
    public function RegisterCSSFile($href_css, $level = 0){
        $this->cssFiles[$level][] = $href_css;
    }

    /**
     * @param array $vars
     * @return array
     */
    public function getVars(...$vars){
        if(!empty($vars)) {
            return array_filter(
                $this->vars,
                function($key) use ($vars){
                    return in_array($key, $vars);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return array_slice($this->vars, 0);
    }

    /**
     * @param array $vars
     * @return bool
     */
    public function MergeVars(array $vars){
        if(is_array($vars)) {
            $this->vars = array_merge($this->vars, $vars);

            return true;
        }

        return false;
    }

    /**
     * @param array $vars
     * @return bool
     */
    public function AssignVars(array $vars){
        if(is_array($vars)) {
            $this->vars = array_slice($vars, 0);

            return true;
        }

        return false;
    }

    /**
     * @param $varname
     * @param $value
     * @param bool $token
     * @return bool
     */
    public function setVars($varname, $value, $token = true){
        if((isset($this->vars[$varname]) == true) && (!$token)) {
            return false;
        }
        $this->vars[$varname] = $value;

        return true;
    }

    /**
     * @param $name
     * @param null $controller
     * @return mixed
     */
    public function Render($name, $controller = null){
        $pathLayout = APP_PATH . DS . 'views' . DS . 'layouts' . DS . $this->layouts . '.php';
        if(!isset($controller)) {
            $controller = $this->controller->controller;
        }
        if(is_string($controller) && (strlen($controller) > 0))
            $contentPage = APP_PATH . DS . 'views' . DS . $controller . DS . $name . '.php';
        else $contentPage = APP_PATH . DS . 'views' . DS . $name . '.php';
        if(file_exists($pathLayout) == false) trigger_error('Layout ' . $this->layouts . ' does not exist.', E_USER_NOTICE);
        if(file_exists($contentPage) == false) trigger_error('Template ' . $name . ' does not exist.', E_USER_NOTICE);
        extract($this->vars);
        ob_start();
        ob_implicit_flush(false);
        try {
            include($contentPage);
        } catch(\Exception $e) {
            echo $e->getMessage();
        }
        $content = ob_get_clean();

        include($pathLayout);

        return;
    }

    /**
     * @param $name
     * @param bool $renderJS
     * @param null $controller
     * @return mixed
     * @throws \Exception
     */
    public function RenderLayout($name, $renderJS = true, $controller = null){
        if(!isset($controller)) $controller = $this->controller->controller;
        if(is_string($controller) && (strlen($controller) > 0))
            $contentPage = APP_PATH . DS . 'views' . DS . $controller . DS . $name . '.php';
        else $contentPage = APP_PATH . DS . 'views' . DS . $name . '.php';
        if(file_exists($contentPage) == false) {
            throw new Exception('Template ' . $name . ' does not exist.');
        }
        extract($this->vars);
        include($contentPage);
        if($renderJS) {
            echo $this->RenderJsLinks();
        }

        return;
    }

    /**
     * @param $name
     * @param bool $renderJS
     * @param null $controller
     * @return string
     * @throws \Exception
     */
    public function RenderLayoutReturn($name, $renderJS = false, $controller = null){
        ob_start();
        ob_implicit_flush(false);
        try {
            $this->RenderLayout($name, $renderJS, $controller);
        } catch(\Exception $e) {
            echo $e->getMessage();
        }

        return ob_get_clean();
    }

    /**
     * @param $key
     * @param $value
     */
    public function setMeta($key, $value){
        $this->meta[$key] = $value;
    }

    /**
     * @param null $key
     * @return null
     */
    public function getMeta($key = null){
        if(isset($key)) return isset($this->meta[$key]) ? $this->meta[$key] : null;

        return $this->meta;
    }
}