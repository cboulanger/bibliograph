<?php
$ini = require('ini.php');
$db = (object) $ini['database'];
$dbconfig = [
  'db' => [
    'class' => 'yii\db\Connection',
    'dsn' => "{$db->type}:host={$db->host};port={$db->port};dbname={$db->admindb}",
    'username' => "{$db->adminname}",
    'password' => "{$db->adminpassw}",
    'charset' => "{$db->encoding}",
    'tablePrefix' => "{$db->tableprefix}",
  ],    
  'testdb' => [
    'class' => 'yii\db\Connection',
    'dsn' => "mysql:host=localhost;port=3306;dbname=tests",
    'username' => "root",
    'password' => "",
    'charset' => "utf8"
    ]
];
$dbconfig['admindb'] = $dbconfig['db'];
$dbconfig['admindb']['dsn'] = "{$db->type}:host={$db->host};port={$db->port};dbname={$db->admindb}";
$dbconfig['userdb'] = $dbconfig['db'];
$dbconfig['userdb']['dsn'] = "{$db->type}:host={$db->host};port={$db->port};dbname={$db->userdb}";
$dbconfig['tmpdb'] = $dbconfig['db'];
$dbconfig['tmpdb']['dsn'] = "{$db->type}:host={$db->host};port={$db->port};dbname={$db->tmp_db}";
return $dbconfig;