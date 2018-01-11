<?php
$config = require "web.php";
$config['id'] = 'bibliograph-test';
$config['components']['db'] = $config['components']['testdb'];
$config['components']['log']['targets'][0]['levels'] =  ['trace','info', 'error', 'warning'];
return $config;