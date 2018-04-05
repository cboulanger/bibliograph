<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
require('server/vendor/autoload.php');
require('server/vendor/yiisoft/yii2/Yii.php');
$config = require 'server/config/web.php';
(new yii\web\Application($config))->run();