<?php

namespace tests\unit\base;

class AAAPhpTest extends \tests\unit\Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  public function testVersion()
  {
    $expected_php_version = $expected_php_version = $_SERVER['PHP_VERSION'];
    $this->tester->expectTo("find PHP version $expected_php_version");
    $actual_php_version = PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
    $this->tester->assertTrue($expected_php_version === $actual_php_version,"Wrong PHP version $actual_php_version, expected $expected_php_version");
  }
}
