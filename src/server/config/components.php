<?php
$components = [
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
        //'levels' => ['info', 'error', 'warning'],
        'levels' => ['trace','info', 'error', 'warning'],
        'except' => ['yii\db\*'],
        'logVars' => [],
        //'exportInterval' => 1
      ]
    ] 
  ],
  // The http response component
  'response' => [
    'class' => \lib\components\EventTransportResponse::class
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