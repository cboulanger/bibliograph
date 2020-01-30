<?php
// server entry point for codeception acceptance tests

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
define('APP_CONFIG_FILE', __DIR__ . "/../config/app.toml");

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');

$config = require __DIR__ . '/../config/web.php';
Yii::setAlias('@app', __DIR__ . "/../..");
Yii::setAlias('@tests', __DIR__ . "/..");

$app = new yii\web\Application($config);

// make sure db connection is opened with utf-8 encoding
$app->db->on(\yii\db\Connection::EVENT_AFTER_OPEN, function ($event) {
  $event->sender->createCommand("SET NAMES utf8")->execute();
});
$app->run();
