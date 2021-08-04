<?php

namespace tests\unit\base;

class _PhpTest extends \tests\unit\Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  public function testVersion()
  {
    $this->tester->wantToTest("if we are running the PHP version");
    $phpversion = PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
    $this->tester->assertEquals($_SERVER["PHP_VERSION"], $phpversion,"Wrong PHP version $phpversion");
  }
}
