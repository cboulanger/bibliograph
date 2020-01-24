<?php
// @todo: next two lines must go away before release
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_DEBUG') or define('YII_DEBUG', true);

require('server/vendor/autoload.php');
require('server/vendor/yiisoft/yii2/Yii.php');
$config = require 'server/config/web.php';
$app = new yii\web\Application($config);
// make sure db connection is opened with utf-8 encoding
$app->db->on(\yii\db\Connection::EVENT_AFTER_OPEN, function ($event) {
  $event->sender->createCommand("SET NAMES utf8")->execute();
});
$app->run();