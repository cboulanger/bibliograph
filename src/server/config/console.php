<?php
$config = require "common.php";
$config['id'] = 'bibliograph-console';
unset($config['components']['response']);
return $config; 