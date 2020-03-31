<?php
//
// Bibliograph server entry point
//
const YII_DEBUG=true;
require __DIR__  . '/../bootstrap.php';
$config = require APP_BACKEND_DIR . '/config/web.php';
$app = new yii\web\Application($config);
// make sure db connection is opened with utf-8 encoding
$app->db->on(\yii\db\Connection::EVENT_AFTER_OPEN, function ($event) {
  $event->sender->createCommand("SET NAMES utf8")->execute();
});
$app->run();
