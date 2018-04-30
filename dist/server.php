<?php
// @todo: next two lines must go away before release
//defined('YII_ENV') or define('YII_ENV', 'dev');
//defined('YII_DEBUG') or define('YII_DEBUG', true);

require('server/vendor/autoload.php');
require('server/vendor/yiisoft/yii2/Yii.php');
$config = require 'server/config/web.php';
(new yii\web\Application($config))->run();