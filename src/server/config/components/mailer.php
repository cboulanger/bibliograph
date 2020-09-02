<?php
use \lib\components\Configuration;

// Mailer component configuration
try {
  $transport = Configuration::anyOf('EMAIL_TRANSPORT', 'email.transport');
} catch (Exception $e) {
  $transport = null;
}
switch ($transport) {
  case null:
    return [];
  case "SMTP":
  case "smtp":
    return [
      'class' => yii\swiftmailer\Mailer::class,
      'transport' => [
        'class' => 'Swift_SmtpTransport',
        'host' => Configuration::anyOf('EMAIL_HOST', 'email.host'),
        'port' => Configuration::anyOf('EMAIL_PORT', 'email.port'),
        'username' => Configuration::anyOf('EMAIL_USERNAME', 'email.username'),
        'password' => Configuration::anyOf('EMAIL_PASSWORD', 'email.password'),
        'encryption' => Configuration::anyOf('EMAIL_ENCRYPTION', 'email.encryption'),
      ]
    ];
  default:
    throw new \yii\base\InvalidConfigException("Transport '$transport' not supported.");
}

