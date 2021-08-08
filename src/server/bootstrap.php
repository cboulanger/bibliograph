<?php
defined("DOTENV_FILE") or define("DOTENV_FILE" , ".env");
if(!file_exists(DOTENV_FILE)) throw new \Exception("Missing environment variable definition file at " . DOTENV_FILE);
defined("APP_BACKEND_DIR") or define("APP_BACKEND_DIR" , __DIR__);
defined("VENDOR_DIR") or define("VENDOR_DIR" , APP_BACKEND_DIR . "/vendor" );
if(!file_exists(VENDOR_DIR . '/autoload.php')) throw new \Exception("Missing composer dependencies at " . VENDOR_DIR);
require VENDOR_DIR . '/autoload.php';
require VENDOR_DIR . '/yiisoft/yii2/Yii.php';
require APP_BACKEND_DIR . '/lib/components/Configuration.php';
(new Symfony\Component\Dotenv\Dotenv())->loadEnv(DOTENV_FILE);
require __DIR__ . '/config/defaults.php';
set_time_limit(MAX_EXECUTION_TIME);
