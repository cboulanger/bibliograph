<?php
$ini = require('ini.php');
$db = (object) $ini['database'];

// check environment variables
foreach(["DB_TYPE","DB_HOST","DB_PORT","DB_USER","DB_PASSWORD"] as $envvar) {
  $missing = [];
  if (!$_SERVER[$envvar]) {
    $missing[] = $envvar;
  }
}
if (count($missing)) {
  throw new \yii\base\InvalidConfigException("Missing database credentials. Please set the following environment variables: " . implode(", ", $missing));
}

return [
  'class' => yii\db\Connection::class,
  'dsn' => "{$_SERVER['DB_TYPE']}:host={$_SERVER['DB_HOST']};port={$_SERVER['DB_PORT']};dbname={$db->admindb}",
  'username' => "{$_SERVER['DB_USER']}",
  'password' => "{$_SERVER['DB_PASSWORD']}",
  'charset' => "{$db->encoding}",
  'tablePrefix' => "{$db->tableprefix}",
];
