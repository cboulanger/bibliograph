<?php
$config = require "common.php";
$config['id'] = 'bibliograph-test';
$config['components']['db'] = [
  'class' => 'yii\db\Connection',
  'dsn' => "mysql:host=localhost;dbname=tests",
  'username' => "root",
  'password' => "",
];
return $config;