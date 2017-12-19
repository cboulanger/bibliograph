<?php
$config =  [
    'id' => 'bibliograph-console',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'bibliograph\controllers',
    'aliases' => [
        '@bibliograph' => dirname(__DIR__),
    ],
    'components' => array_merge(
        require('db.php'), [
        'request' => [
            'enableCookieValidation' => true,
            'enableCsrfValidation' => true,
            'cookieValidationKey' => 'a1a2a3a3d3d4g5g4hfgfh4g',
        ],
    ]),
    'extensions' => require(__DIR__ . '/../vendor/yiisoft/extensions.php'),
];
if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}
return $config;