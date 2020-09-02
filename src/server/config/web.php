<?php
//
// configuration for bibliograph backend
//
try {
  $config = require __DIR__ . "/config.php";
} catch (\Throwable $e) {
  require __DIR__ . "/../lib/util/Server.php";
  die(\lib\util\Server::createExceptionResponse($e));
}

$config['id'] = 'bibliograph-server';
return $config;
