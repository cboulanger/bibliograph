<?php
$required_constants = ["DOTENV_FILE"];
foreach ($required_constants as $constant) {
  if (!defined($constant)) {
    throw new \yii\base\InvalidConfigException("Constant '$constant' must be declared in startup script");
  }
}
defined("APP_BACKEND_DIR") or define( "APP_BACKEND_DIR" , __DIR__);
require APP_BACKEND_DIR . '/vendor/autoload.php';
require APP_BACKEND_DIR . '/vendor/yiisoft/yii2/Yii.php';
require APP_BACKEND_DIR . '/lib/components/Configuration.php';
// load environment variables
(new Symfony\Component\Dotenv\Dotenv())->loadEnv( DOTENV_FILE);
// load defaults, which might depend on env vars
require __DIR__ . '/config/defaults.php';
// timeout for server scripts
set_time_limit(MAX_EXECUTION_TIME);
