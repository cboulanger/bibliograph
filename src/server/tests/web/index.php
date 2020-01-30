<?php
//
// server entry point for codeception acceptance tests
//
define('DOTENV_FILE','test.env');
define('APP_CONFIG_FILE', __DIR__ . "/../config/test.toml");
require __DIR__  . '/../../bootstrap.php';
$config = require APP_BACKEND_DIR . '/config/web.php';
$app = new yii\web\Application($config);
Yii::setAlias('@tests', dirname(__DIR__) . '/tests');
// make sure db connection is opened with utf-8 encoding
$app->db->on(\yii\db\Connection::EVENT_AFTER_OPEN, function ($event) {
  $event->sender->createCommand("SET NAMES utf8")->execute();
});
$app->run();
