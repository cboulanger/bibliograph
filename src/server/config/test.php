<?php
$config = require "web.php";
$config['id'] = 'bibliograph-test';
Yii::setAlias('@tests', __DIR__ . '/../tests');
$config['components']['db'] = [
  'class' => yii\db\Connection::class,
  'dsn' => "mysql:host=127.0.0.1;port=3306;dbname=tests",
  'username' => "root",
  'password' => "",
  'charset' => "utf8"
];
return $config;
