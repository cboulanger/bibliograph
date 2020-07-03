<?php
/* ************************************************************************

  Bibliograph. The open source online bibliographic data manager

  http://www.bibliograph.org

  Copyright:
    2003-2020 Christian Boulanger

  License:
    MIT license
    See the LICENSE file in the project's top-level directory for details.

  Authors:
    Christian Boulanger (@cboulanger) info@bibliograph.org

************************************************************************ */

/*
 * Server entry point
 */

const APP_ROOT_DIR = __DIR__;
const APP_FRONTEND_DIR = __DIR__ . "/bibliograph";
const DOTENV_FILE= __DIR__ . "/config/.env";
const APP_CONFIG_FILE = __DIR__ . "/config/app.conf.toml";

require __DIR__  . '/server/bootstrap.php';
$config = require 'server/config/web.php';
$app = new yii\web\Application($config);

// make sure db connection is opened with utf-8 encoding
$app->db->on(\yii\db\Connection::EVENT_AFTER_OPEN, function ($event) {
  $event->sender->createCommand("SET NAMES utf8")->execute();
});
$app->run();
