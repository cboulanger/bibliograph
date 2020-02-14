<?php
//
// configuration for bibliograph backend
//
$config = require "config.php";
$config['id'] = 'bibliograph-server';
$config['components']['request'] = [
  'enableCookieValidation' => true,
  'enableCsrfValidation' => false
];
return $config;
