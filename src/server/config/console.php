<?php
$config = require "common.php";
$config['id'] = 'bibliograph-console';
unset($config['components']['response']);
unset($config['on beforeRequest']);
// disable module loader for the moment
//unset($config['components']['moduleLoader']);
//$config['bootstrap']=['log'];
return $config; 