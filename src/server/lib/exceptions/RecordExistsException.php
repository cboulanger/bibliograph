<?php

namespace lib\exceptions;

/**
 * An Exception that is thrown when inserting a record that
 * already exists
 * @package lib\exceptions
 */
class RecordExistsException extends \yii\db\Exception{}