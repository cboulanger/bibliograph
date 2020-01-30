<?php
use \lib\components\Configuration;

// Mailer component configuration
$mailer =[];
if (Configuration::iniValue('email.transport')) {
  $mailer['class'] = yii\swiftmailer\Mailer::class;
  if (Configuration::iniValue('email.transport') === "smtp"){
    $mailer['transport'] = [
      'class'       => 'Swift_SmtpTransport',
      'host'        => Configuration::iniValue('email.host') ?? null,
      'port'        => Configuration::iniValue('email.port') ?? null,
      'username'    => Configuration::iniValue('email.username') ?? null,
      'password'    => Configuration::iniValue('email.password') ?? null,
      'encryption'  => Configuration::iniValue('email.encryption') ?? null,
    ];
  }
}
return $mailer;
