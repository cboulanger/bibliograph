<?php
/**
 * Loggin configuration
 */

use \lib\components\Configuration;
use \yii\log\FileTarget;

$log_config = [
  'targets' => [
    // exceptions go into error.log
    'error' => [
      'class' => FileTarget::class,
      'levels' => ['error'],
      'logFile' => APP_LOG_DIR . "/error.log",
      'logVars' => [],
      'except' => ['yii\web\HttpException*'],
      'exportInterval' => 1
    ],
    // everything else into app.log
    'app' => [
      'class' => FileTarget::class,
      'levels' => YII_DEBUG_LEVELS,
      'categories' => YII_DEBUG_CATEGORIES,
      'logFile' => APP_LOG_DIR . "/" . APP_LOG_NAME,
      'logVars' => [],
      'exportInterval' => 1
    ],
  ]
];

// Do we have an error email target?
$from     = Configuration::get('email.errors_from');
$to       = Configuration::get('email.errors_to');
$subject  = Configuration::get('email.errors_subject');
$except   = ['jsonrpc','yii\web\HttpException*'];
$logVars = []; // don't log any, might contain passwords etc.
if ($from and $to and $subject) {
  $log_config['targets']['mail'] = [
    'class' => \yii\log\EmailTarget::class,
    'mailer' => 'mailer',
    'levels' => ['error'],
    'except' => $except,
    'logVars' => $logVars,
    'message' => [
      'from' => [$from],
      'to' => [$to],
      'subject' => $subject,
    ]
  ];
}
return $log_config;
