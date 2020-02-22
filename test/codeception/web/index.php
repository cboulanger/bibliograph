<?php
//
// server entry point for codeception acceptance tests
//
ini_set("xdebug.collect_params", 1);
define('YII_ENV', "test");
define('YII_DEBUG', true);
define('APP_ROOT_DIR', realpath(__DIR__ . "/../../.."));
define('APP_BACKEND_DIR', APP_ROOT_DIR . "/src/server");
define('APP_LOG_DIR', APP_ROOT_DIR . "/log/app");
define('DOTENV_FILE', APP_ROOT_DIR . "/test/.env");
define( "APP_CONFIG_FILE" , APP_ROOT_DIR . "/test/app.conf.toml");

// run app
require APP_BACKEND_DIR  . '/bootstrap.php';
$config = require_once __DIR__ . '/../config/web-test.php';
$app = new yii\web\Application($config);
#throw new Exception("foo!");
$app->run();
