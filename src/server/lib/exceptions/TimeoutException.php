<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 06.05.18
 * Time: 08:35
 */

namespace lib\exceptions;


class TimeoutException extends Exception
{
  const CODE = Exception::CODE_TIMEOUT;
}
