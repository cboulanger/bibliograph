<?php
$ini = require('ini.php');
$db = (object) $ini['database'];
return [
    'db' => [
        'class' => 'yii\db\Connection',
        'dsn' => "{$db->type}:host={$db->host};dbname={$db->admindb}",
        'username' => "{$db->adminname}",
        'password' => "{$db->adminpassw}",
        'charset' => "{$db->encoding}",
        'tablePrefix' => "{$db->tableprefix}",
    ],    
    'admindb' => [
        'class' => 'yii\db\Connection',
        'dsn' => "{$db->type}:host={$db->host};dbname={$db->admindb}",
        'username' => "{$db->adminname}",
        'password' => "{$db->adminpassw}",
        'charset' => "{$db->encoding}",
        'tablePrefix' => "{$db->tableprefix}",
    ],
    'userdb' => [
        'class' => 'yii\db\Connection',
        'dsn' => "{$db->type}:host={$db->host};dbname={$db->userdb}",
        'username' => "{$db->adminname}",
        'password' => "{$db->adminname}",
        'charset' => "{$db->encoding}",
        'tablePrefix' => "{$db->tableprefix}",
    ],
    'tmpdb' => [
        'class' => 'yii\db\Connection',
        'dsn' => "{$db->type}:host={$db->host};dbname={$db->tmp_db}",
        'username' => "{$db->adminname}",
        'password' => "{$db->adminname}",
        'charset' => "{$db->encoding}",
        'tablePrefix' => "{$db->tableprefix}",
    ],       
];