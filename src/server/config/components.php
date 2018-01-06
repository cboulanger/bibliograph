<?php
$components = [
  // request component
  'request' => [
    'enableCookieValidation' => true,
    'enableCsrfValidation' => true,
    'cookieValidationKey' => 'a1a2a3a3d3d4g5g4hfgfh4g',
  ],
  // identity class
  'user' => [
    'class' => 'yii\web\User',
    'identityClass' => 'app\models\User',
  ],      
  // logging
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
  // a queue of Events to be transported to the browser
  'eventQueue' => [
    'class' => \lib\components\EventQueue::class
  ],
  // various utility methods
  'utils' => [ 
    'class' => \lib\components\Utils::class
  ],
  // server-side events, not working yet
  'sse' => [
    'class' => \odannyc\Yii2SSE\LibSSE::class
  ],
  // message channels, not working yet
  'channel' => [
    'class' => \lib\channel\Component::class
  ]
];
return array_merge(
  require('db.php'),
  $components
);