<?php

namespace lib\util;

class Server {
  static function createExceptionResponse(\Throwable $exception) {
    $isDebug = (defined('YII_DEBUG') && YII_DEBUG) or $_SERVER['YII_DEBUG'];
    switch ($_SERVER['HTTP_ACCEPT']) {
      case "application/json":
        try {
         $request = json_decode(file_get_contents("php://input"));
         $id = $request->id;
        } catch (\Throwable $e) {
          return "CONFIGURATION ERROR: Unable to parse request";
        }
       return json_encode([
        'jsonrpc' => "2.0",
        'id' => $id,
        'error' => [
          'code' => -32603,
          'message' => "Internal Error",
          'data' => $isDebug ? (array) $exception : null
        ]
      ]);
      default:
        return $exception->__toString();
    }
  }
}
