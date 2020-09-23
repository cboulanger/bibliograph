<?php

namespace lib\exceptions;

use georgique\yii2\jsonrpc\exceptions\JsonRpcException;

/**
 * Base class for all exceptions thrown in this application and an index of error codes
 * for all subclasses
 *
 * @package lib\exceptions
 */
class Exception extends JsonRpcException
{
  const CODE = 1;

  const CODE_UNSPECIFIED = 1;
  const CODE_USER_ERROR = 2;
  const CODE_ACCESS_DENIED = 3;
  const CODE_RECORD_EXISTS = 4;
  const CODE_SETUP = 5;
  const CODE_TIMEOUT = 6;
  const CODE_SERVER_BUSY = 7;

  /**
   * Additional text that migh be useful in investigating the
   * reason for the exception
   * @var string
   */
  public $diagnosticOutput = "";


  public function __construct(string $message = "", $data = [], \Throwable $previous = null)
  {
    parent::__construct($message, static::CODE, $data, $previous);
  }
}
