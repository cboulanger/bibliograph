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
      'exportInterval' => 1
    ],
    // everything else into app.log
    'app' => [
      'class' => FileTarget::class,
      'levels' => ['trace','info', 'warning'],
      'except' => ['yii\*','auth'],
      'logFile' => APP_LOG_DIR . "/app.log",
      'logVars' => [],
      'exportInterval' => 1
    ],
  ]
];
// Do we have an error email target?

if (Configuration::iniValue('email.errors_from')
  and Configuration::iniValue('email.errors_to')
  and Configuration::iniValue('email.errors_subject') )
{
  $log_config['targets']['mail'] = [
    'class' => \yii\log\EmailTarget::class,
    'mailer' => 'mailer',
    'levels' => ['error'],
    'except' => ['jsonrpc','yii\web\HttpException*'],
    'message' => [
      'from' => [Configuration::iniValue('email.errors_from')],
      'to' => [Configuration::iniValue('email.errors_to')],
      'subject' => Configuration::iniValue('email.errors_subject'),
    ],
  ];
}
return $log_config;
