<?php
$components = array_merge(
  require('db.php'), 
  [
  'user' => [
    'class' => 'yii\web\User',
    'identityClass' => 'app\models\User',
  ],      
  'utils' => [ 'class' => 'lib\components\Utils'],
  'log' => [
    'targets' => [
      [
        'class' => 'yii\log\FileTarget',
        'levels' => ['info', 'error', 'warning'],
        //'levels' => ['trace','info', 'error', 'warning'],
        'except' => ['yii\db\*'],
        'logVars' => []
      ]
    ] 
  ],
  'sse' => [
    'class' => \odannyc\Yii2SSE\LibSSE::class
  ],
  'channel' => [
    'class' => \lib\channel\Component::class
  ]
]);
return $components;