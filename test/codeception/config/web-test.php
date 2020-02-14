<?php
//
// configuration for test backend
//

// the environment variables must be read again because they are overwritten in functional testing
(new Symfony\Component\Dotenv\Dotenv())->load( DOTENV_FILE);
$config = require APP_BACKEND_DIR . '/config/web.php';
return $config;
