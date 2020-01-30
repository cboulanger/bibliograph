<?php
$config = require __DIR__ ."/../../config/web.php";
$config['id'] = 'bibliograph-test';
$config['components']['db'] = [
  'class' => yii\db\Connection::class,
  'dsn' => "mysql:host=host.docker.internal;port=3306;dbname=tests",
  'username' => "root",
  'password' => "bibliograph",
  'charset' => "utf8"
];
return $config;
