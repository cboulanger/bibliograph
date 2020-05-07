<?php

namespace lib\exceptions;

/**
 * An Exception that is thrown when inserting a record that
 * already exists
 * @package lib\exceptions
 */
class RecordExistsException extends Exception
{
  const CODE = Exception::CODE_RECORD_EXISTS;
}
