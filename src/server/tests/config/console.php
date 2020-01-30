<?php
$config = require __DIR__ . "/../../config/common.php";
$config['id'] = 'bibliograph-console-test';
$config['components']['db'] = [
  'class' => yii\db\Connection::class,
  'dsn' => "mysql:host=host.docker.internal;port=3306;dbname=tests",
  'username' => "root",
  'password' => "bibliograph",
  'charset' => "utf8"
];
unset($config['components']['response']);
unset($config['on beforeRequest']);
return $config;
