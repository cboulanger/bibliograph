<?php
//
// configuration for test server
//
defined("APP_ROOT_DIR") or define("APP_ROOT_DIR", __DIR__ . '/../../../..' );
defined("APP_BACKEND_DIR") or define( "APP_BACKEND_DIR" , APP_ROOT_DIR . "/src/server");
(new Symfony\Component\Dotenv\Dotenv())->load(APP_ROOT_DIR . "/test.env");
$config = require APP_BACKEND_DIR . "/config/web.php";
$config['id'] = 'bibliograph-test';
Yii::setAlias('@tests', APP_BACKEND_DIR . '/tests');
return $config;
