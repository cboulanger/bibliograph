<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 24.02.18
 * Time: 11:40
 */

namespace lib\exceptions;

/**
 * An exception which is thrown if a setup procedure fails
 * @package lib\exceptions
 */
class SetupException extends \RuntimeException {

  /**
   * Additional text that migh be useful in investigating the
   * reason for the exception
   * @var string
   */
  public $diagnosticOutput = "";
}