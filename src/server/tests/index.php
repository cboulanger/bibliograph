<?php
// server entry point for codeception tests
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
define('APP_CONFIG_FILE',  __DIR__ . "/test.config.toml" );
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
$config = require __DIR__ . '/../config/test.php';
(new yii\web\Application($config))->run();