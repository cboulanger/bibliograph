<?php
//
// Database connection
//

use \lib\components\Configuration;

// check environment variables; to do allow setting from ini file, too
$missing = [];
foreach(["DB_TYPE","DB_HOST","DB_PORT","DB_USER","DB_PASSWORD","DB_DATABASE"] as $envvar) {
  if (!isset($_SERVER[$envvar]) or !$_SERVER[$envvar]) {
    $missing[] = $envvar;
  }
}
if (count($missing)) {
  throw new \yii\base\InvalidConfigException("Missing database credentials. Please set the following environment variables: " . implode(", ", $missing));
}

// get settings from Environment or ini file (to do)
try {
  $charset = ($v = Configuration::iniValue('database.encoding')) ? $v : "utf8";
  $db_type  = Configuration::anyOf('DB_TYPE', 'database.type');
  $db_host  = Configuration::anyOf('DB_HOST', 'database.host');
  $db_port  = Configuration::anyOf('DB_PORT', 'database.port');
  $db_name  = Configuration::anyOf('DB_DATABASE', 'database.name');
  $db_user  = Configuration::anyOf('DB_USER', 'database.user');
  $db_passw = Configuration::anyOf('DB_PASSWORD', 'database.password');
  $table_prefix = Configuration::iniValue('database.tableprefix');
} catch (\Exception $e) {
  throw new \yii\base\InvalidConfigException($e->getMessage());
}

return [
  'class' => yii\db\Connection::class,
  'dsn' => "{$db_type}:host={$db_host};port={$db_port};dbname={$db_name};charset={$charset}",
  'username' => "{$db_user}",
  'password' => "{$db_passw}",
  'charset' => $charset,
  'tablePrefix' => $table_prefix
];
