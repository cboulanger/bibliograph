<?php
echo "Bootstrapped!";
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ .'/../vendor/autoload.php';
$config = require('config/test.php');
(new yii\web\Application($config));