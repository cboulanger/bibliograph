<?php

namespace lib\exceptions;



/**
 * An exception that is thrown if the requesting user does not have the required access rights
 * to execute the jsonrpc method
 * @package lib\exceptions
 */
class AccessDeniedException extends Exception
{
  const CODE = Exception::CODE_ACCESS_DENIED;

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
