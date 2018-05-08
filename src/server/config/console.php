<?php
$config = require "common.php";
$config['id'] = 'bibliograph-console';
unset($config['components']['response']);
// disable module loader for the moment
unset($config['components']['moduleLoader']);
unset($config['components']['response']);
unset($config['on beforeRequest']);
$config['bootstrap']=['log'];
return $config; 