<?php
//
// configuration for test backend
//

$dotenvLoader = new Symfony\Component\Dotenv\Dotenv();
$dotenvLoader->load(DOTENV_FILE);
// PHP-Version-dependent variables
$dotenvLoader->overload(DOTENV_FILE . ".dev");
$config = require APP_BACKEND_DIR . '/config/web.php';
return $config;
