<?php

namespace lib\exceptions;

/**
 * An exception that is thrown in response to user input. Does not require a stack
 * trace since the cause of the error is not in the code.
 * should be replaced by \yii\base\UserException
 * @package lib\exceptions
 */
class UserErrorException extends \yii\base\UserException {}
