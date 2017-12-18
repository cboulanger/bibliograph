<?php
$ini = parse_ini_file ( __DIR__ . "/../../services/config/bibliograph.ini.php", true, INI_SCANNER_RAW );
$config =  [
    'id' => 'bibliograph-server',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'bibliograph\controllers',
    'aliases' => [
        '@bibliograph' => dirname(__DIR__),
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => "{$ini['database']['type']}:host={$ini['database']['host']};dbname={$ini['database']['admindb']}",
            'username' => 'bibliograph',
            'password' => 'bibliograph',
            'charset' => 'utf8',
        ],
        'request' => [
            'enableCookieValidation' => true,
            'enableCsrfValidation' => true,
            'cookieValidationKey' => 'a1a2a3a3d3d4g5g4hfgfh4g',
        ],
    ],
    'extensions' => require(__DIR__ . '/../vendor/yiisoft/extensions.php'),
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}
return $config;