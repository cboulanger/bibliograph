<?php
defined( "APP_INI_FILE") or define( "APP_INI_FILE" ,  __DIR__ . "/../tests/test.ini.php" );
$config = require "web.php";
$config['id'] = 'bibliograph-test';
Yii::setAlias('@tests', __DIR__ . '/../tests');
return $config;