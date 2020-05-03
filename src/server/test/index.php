<?php
//
// Bibliograph server entry point for tests
//

ini_set('max_execution_time', 300); //300 seconds = 5 minutes
set_time_limit(300);

const YII_DEBUG=true;
const YII_ENV_TEST=true;
define('APP_ROOT_DIR', realpath(__DIR__ . "/../../.."));
define('APP_BACKEND_DIR', APP_ROOT_DIR . "/src/server");
define('APP_LOG_DIR', APP_ROOT_DIR . "/log/app");
define('DOTENV_FILE', APP_ROOT_DIR . "/test/.env");
define( "APP_CONFIG_FILE" , APP_ROOT_DIR . "/test/app.conf.toml");

require __DIR__  . '/../bootstrap.php';
$config = require APP_BACKEND_DIR . '/config/web.php';
$app = new yii\web\Application($config);
$app->run();
