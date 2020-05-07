<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 24.02.18
 * Time: 11:40
 */

namespace lib\exceptions;

/**
 * An exception which is thrown if the JSON-RPC server currently cannot process the
 * request. The client should wait a certain period of time and try again.
 * @package lib\exceptions
 */
class ServerBusyException extends Exception {

  const CODE = Exception::CODE_SERVER_BUSY;

}
