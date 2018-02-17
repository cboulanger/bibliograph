<?php
namespace lib;

class Validate
{
  /**
   * Checks if condition is true
   *
   * @param bool $condition
   * @param string $error_msg
   * @return void
   * @throws Exception
   */
  public static function isTrue( $condition, $error ){
    if( ! $condition ) {
      $exception = $error instanceof \Exception ? $error : new \Exception( $error );
      throw $exception;
    }
  }

  /**
   * Checks if condition is false
   *
   * @param bool $condition
   * @param string $error_msg
   * @return void
   * @throws Exception
   */
  public static function isFalse( $condition, $error ){
    self::isTrue( ! $condition, $error );
  }

  /**
   * Checks if argument is not null
   *
   * @param mixed $arg  
   * @param string $error_msg
   * @return void
   * @throws Exception
   */
  public static function isNotNull( $arg, $error ){
    self::isTrue( $arg !== null, $error );
  }  
}