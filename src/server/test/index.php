<?php
//
// Bibliograph server entry point for tests
//

ini_set('max_execution_time', 300); //300 seconds = 5 minutes

const YII_DEBUG = true;
const YII_ENV_TEST = true;
const APP_ROOT_DIR = __DIR__ . "/../../..";
const APP_LOG_DIR = APP_ROOT_DIR . "/log/app";
const DOTENV_FILE = APP_ROOT_DIR . "/test/.env";
const APP_CONFIG_FILE = APP_ROOT_DIR . "/test/app.conf.toml";
const MAX_EXECUTION_TIME = 300;

require __DIR__  . '/../bootstrap.php';
$config = require APP_BACKEND_DIR . '/config/web.php';
$app = new yii\web\Application($config);
$app->run();
