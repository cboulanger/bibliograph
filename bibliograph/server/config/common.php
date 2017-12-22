<?php
require "constants.php";
$config =  [
  'basePath' => dirname(__DIR__),
  'controllerNamespace' => 'app\controllers',
  'aliases' => [
    '@lib'   => __DIR__ . "/../lib/",
    '@tests' => __DIR__ . "/../tests/",
  ],
  'components' => array_merge(
    require('db.php'), [
    'utils' => [ 'class' => 'lib\component\Utils'],        
  ]),
  'extensions' => require(__DIR__ . '/../vendor/yiisoft/extensions.php'),
];
if (YII_ENV_DEV) {
  $config['bootstrap'][] = 'gii';
  $config['modules']['gii'] = [
    'class' => 'yii\gii\Module',
    'generators' => [
      'fixture' => [
        'class' => 'elisdn\gii\fixture\Generator',
      ],
    ]
  ];
  $config['controllerMap'] = [
    'fixture' => [
      'class' => 'yii\faker\FixtureController',
    ],
  ];    
}
return $config;