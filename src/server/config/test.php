<?php
$config = require "web.php";
$config['id'] = 'bibliograph-test';
$config['components']['db'] = $config['components']['testdb'];
return $config;