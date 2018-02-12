<?php
defined( "APP_INI_FILE") or define( "APP_INI_FILE" ,  __DIR__ . "/../tests/test.ini.php" );
$config = require "web.php";
$config['id'] = 'bibliograph-test';
$config['components']['log']['targets'][0]['levels'] =  ['trace','info', 'error', 'warning'];
return $config;