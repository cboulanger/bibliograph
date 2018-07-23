<?php

namespace lib\exceptions;

/**
 * An exception that is thrown if the requesting user does not have the required access rights
 * to execute the jsonrpc method
 * @package lib\exceptions
 */
class AccessDeniedException extends \JsonRpc2\Exception {}