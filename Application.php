<?php

namespace app\core;

use Exception;

/**
 * Class Application
 * @package core
 *
 * @property Router router
 * @property KeyStorage keystorage
 *
 * @inheritdoc
 * @method KeyStorage keyStorage()
 */
class Application extends Core{

    /**
     * @var KeyStorage
     */
    protected $keystorage;

    /**
     * @throws \Exception
     */
    protected function init(){
        parent::init();
        $this->registerHandlers();

        $this->SelectDB('default');
        $this->router = new Router($this);
        $this->keystorage = new KeyStorage();

        $this->initMailer();
        $this->keystorage->init();
        $this->router->init();
    }

    /**
     * @throws \Exception
     */
    public function run(){
        $this->router->handle();
    }

    /**
     *
     * @throws \Exception
     */
    protected function initMailer(){
        $this->mailer = $this->config('mailer');

        $system_emails_debug = !is_null($this->keystorage->system_emails_debug) ? $this->keystorage->system_emails_debug : 1;
        $system_emails_host = !is_null($this->keystorage->system_emails_host) ? $this->keystorage->system_emails_host : '';
        $system_emails_port = !is_null($this->keystorage->system_emails_port) ? $this->keystorage->system_emails_port : '';
        $system_emails_user_name = !is_null($this->keystorage->system_emails_user_name) ? $this->keystorage->system_emails_user_name : '';
        $system_emails_password = !is_null($this->keystorage->system_emails_password) ? $this->keystorage->system_emails_password : '';
        $system_emails_encryption = !is_null($this->keystorage->system_emails_encryption) ? $this->keystorage->system_emails_encryption : '';

        if($system_emails_debug == 1) {
            $this->mailer['useFileTransport'] = true;
        } else {
            if(empty($this->mailer['transport']) || empty($this->mailer['transport']['class'])) {
                $this->mailer['transport']['class'] = 'Swift_SmtpTransport';
            }
            if(!empty($system_emails_host)) {
                $this->mailer['transport']['host'] = $system_emails_host;
            }
            if(!empty($system_emails_user_name)) {
                $this->mailer['transport']['username'] = $system_emails_user_name;
            }
            if(!empty($system_emails_password)) {
                $this->mailer['transport']['password'] = $system_emails_password;
            }
            if(!empty($system_emails_port)) {
                $this->mailer['transport']['port'] = $system_emails_port;
            }
            if(!empty($system_emails_encryption)) {
                $this->mailer['transport']['encryption'] = $system_emails_encryption;
            }
        }

        if(empty($this->mailer)) {
            throw new Exception('Mailer is not configured!');
        }
    }


}