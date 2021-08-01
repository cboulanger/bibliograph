<?php
//
// server entry point for codeception acceptance tests
//
ini_set("xdebug.collect_params", 1);
const YII_ENV = "test";
const YII_DEBUG = true;

define('APP_ROOT_DIR', realpath(__DIR__ . "/../../.."));
const APP_BACKEND_DIR = APP_ROOT_DIR . "/src/server";
const DOTENV_FILE = APP_ROOT_DIR . "/test/.env";
const APP_CONFIG_FILE = APP_ROOT_DIR . "/test/app.conf.toml";
const APP_LOG_DIR = APP_ROOT_DIR . "/log/app";
const JSON_RPC_USE_PAYLOAD_TOKEN_AUTH = true;
const VENDOR_DIR = APP_ROOT_DIR . "/src/lib/composer-" . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "/vendor";

require APP_BACKEND_DIR  . '/bootstrap.php';
$config = require_once __DIR__ . '/../config/web-test.php';
$app = new yii\web\Application($config);
$app->run();
