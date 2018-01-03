<?php
require "constants.php";
$config =  [
  'basePath' => dirname(__DIR__),
  'bootstrap' => ['log'],
  'controllerNamespace' => 'app\controllers',
  'aliases' => [
  '@lib'   => __DIR__ . "/../lib/",
  '@tests' => __DIR__ . "/../tests/",
  ],
  'components' => array_merge(
  require('db.php'), [
  'user' => [
    'class' => 'yii\web\User',
    'identityClass' => 'app\models\User',
  ],      
  'utils' => [ 'class' => 'lib\components\Utils'],
  'log' => [
    'targets' => [
    [
      'class' => 'yii\log\FileTarget',
      //'levels' => ['trace','info', 'error', 'warning'],
      'except' => ['yii\*'],
      'logVars' => []
    ]
    ]
    ],
    'sse' => [
      'class' => \odannyc\Yii2SSE\LibSSE::class
    ] 
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
  'migration' => [
    'class' => 'bizley\migration\controllers\MigrationController',
  ],    
  ];    
}
return $config;