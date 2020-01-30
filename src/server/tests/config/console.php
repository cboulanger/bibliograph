<?php
//
// configuration for yii cli in test mode
//
$config = require "web.php";
$config['id'] = 'bibliograph-console-test';
unset($config['components']['response']);
unset($config['components']['request']);
unset($config['on beforeRequest']);
return $config;
