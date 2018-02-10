<?php
require "parts/constants.php";

// Production
$config =  [
  'basePath' => dirname(__DIR__),
  'bootstrap' => ['log','channel'],
  'controllerNamespace' => 'app\controllers',
  'aliases' => [
    '@lib'   => __DIR__ . "/../lib/",
    '@tests' => __DIR__ . "/../tests/",
    ],
  'components' => require('parts/components.php'),
  'extensions' => require(__DIR__ . '/../vendor/yiisoft/extensions.php'),
];

// Development
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
    'migration' => [
      'class' => 'bizley\migration\controllers\MigrationController',
    ],    
  ];    
}
return $config;