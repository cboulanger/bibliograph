<?php
//
// Database connection
//

use \lib\components\Configuration;

// check environment variables
$missing = [];
foreach(["DB_TYPE","DB_HOST","DB_PORT","DB_USER","DB_PASSWORD","DB_DATABASE"] as $envvar) {
  if (!isset($_SERVER[$envvar]) or !$_SERVER[$envvar]) {
    $missing[] = $envvar;
  }
}
if (count($missing)) {
  throw new \yii\base\InvalidConfigException("Missing database credentials. Please set the following environment variables: " . implode(", ", $missing));
}

return [
  'class' => yii\db\Connection::class,
  'dsn' => "{$_SERVER['DB_TYPE']}:host={$_SERVER['DB_HOST']};port={$_SERVER['DB_PORT']};dbname={$_SERVER['DB_DATABASE']}",
  'username' => "{$_SERVER['DB_USER']}",
  'password' => "{$_SERVER['DB_PASSWORD']}",
  'charset' => Configuration::iniValue('database.encoding'),
  'tablePrefix' => Configuration::iniValue('database.tableprefix'),
];
