<?php
$config = require "common.php";
$config['id'] = 'bibliograph-test';
$config['components']['db'] = $config['components']['testdb'];
return $config;