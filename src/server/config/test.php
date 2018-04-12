<?php
defined( "APP_CONFIG_FILE") or define( "APP_CONFIG_FILE" ,  __DIR__ . "/../tests/test.ini.php" );
$config = require "web.php";
$config['id'] = 'bibliograph-test';
Yii::setAlias('@tests', __DIR__ . '/../tests');
$config['components']['testdb'] = [
  'class' => yii\db\Connection::class,
  'dsn' => "mysql:host=localhost;port=3306;dbname=tests",
  'username' => "root",
  'password' => "",
  'charset' => "utf8"
];
return $config;