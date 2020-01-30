<?php
//
// configuration for bibliograph backend
//
$config = require "yii-appconfig.php";
$config['id'] = 'bibliograph-server';
$config['components']['request'] = [
  'enableCookieValidation' => true,
  'enableCsrfValidation' => true,
  'cookieValidationKey' => 'a1a2a3a3d3d4g5g4hfgfh4g',
];
return $config;
