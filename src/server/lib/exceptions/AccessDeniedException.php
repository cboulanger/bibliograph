<?php

namespace lib\exceptions;

use georgique\yii2\jsonrpc\exceptions\JsonRpcException;

/**
 * An exception that is thrown if the requesting user does not have the required access rights
 * to execute the jsonrpc method
 * @package lib\exceptions
 */
class AccessDeniedException extends JsonRpcException
{
  const CODE = -32600;

  public function __construct(string $message = "", $data = [], \Throwable $previous = null)
  {
    parent::__construct($message, static::CODE, $data, $previous);
  }

  /**
   * @inheritdoc
   */
  public function getName()
  {
    return 'Not allowed';
  }

}
