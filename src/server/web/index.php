<?php
//
// Bibliograph development server entry point
//

const YII_DEBUG = true;
const APP_ROOT_DIR = __DIR__ . "/../../..";
const APP_LOG_DIR = APP_ROOT_DIR . "/log/app";
const DOTENV_FILE = APP_ROOT_DIR . "/test/.env";
const APP_CONFIG_FILE = APP_ROOT_DIR . "/test/app.conf.toml";

require __DIR__  . '/../bootstrap.php';
$config = require APP_BACKEND_DIR . '/config/web.php';
$app = new yii\web\Application($config);
$app->run();
