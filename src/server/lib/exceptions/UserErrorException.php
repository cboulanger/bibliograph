<?php

namespace lib\exceptions;

use georgique\yii2\jsonrpc\exceptions\JsonRpcException;

/**
 * An exception that is thrown in response to user input. Does not require a stack
 * trace since the cause of the error is not in the code.
 * should be replaced by \yii\base\UserException
 * @package lib\exceptions
 */
class UserErrorException extends JsonRpcException
{
  const CODE = 2;

  public function __construct(string $message = "", $data = [], \Throwable $previous = null)
  {
    parent::__construct($message, static::CODE, $data, $previous);
  }

  /**
   * @inheritdoc
   */
  public function getName()
  {
    return 'User input error';
  }
}
