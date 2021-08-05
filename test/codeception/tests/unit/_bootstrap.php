<?php
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('APP_ROOT_DIR') or define('APP_ROOT_DIR', realpath(__DIR__ . "/../../../.."));
defined('APP_BACKEND_DIR') or define('APP_BACKEND_DIR', APP_ROOT_DIR . "/src/server");
defined('APP_TESTS_DIR') or define('APP_TESTS_DIR', APP_ROOT_DIR . "/test/codeception");
define('APP_LOG_DIR', APP_ROOT_DIR . "/log/app");
defined('DOTENV_FILE') or define('DOTENV_FILE', APP_ROOT_DIR . "/test/.env");
const APP_CONFIG_FILE = APP_ROOT_DIR . "/test/app.conf.toml";
const VENDOR_DIR = APP_ROOT_DIR . "/src/lib/composer-" . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "/vendor";
$_ENV['PHP_VERSION'] = $_SERVER['PHP_VERSION'] = trim(file_get_contents(APP_ROOT_DIR . "/test/php_version.txt"));
require APP_BACKEND_DIR . "/bootstrap.php";
Yii::setAlias('@tests', APP_TESTS_DIR . "/tests");
ob_start();
