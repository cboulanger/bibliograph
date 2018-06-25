<?php
$config = require "common.php";
$config['id'] = 'bibliograph-console-test';
$config['components']['db'] = [
  'class' => yii\db\Connection::class,
  'dsn' => "mysql:host=localhost;port=3306;dbname=tests",
  'username' => "root",
  'password' => "",
  'charset' => "utf8"
];
unset($config['components']['response']);
unset($config['on beforeRequest']);
return $config;