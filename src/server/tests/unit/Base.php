<?php

namespace app\tests\unit;

// for whatever reason, this is not loaded early enough
use PHPUnit\Framework\TestResult;

require_once __DIR__ . "/../_bootstrap.php";

/**
 * Undocumented class
 */
class Base extends \Codeception\Test\Unit
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  /**
   * Count elements of an object
   * @link http://php.net/manual/en/countable.count.php
   * @return int The custom count as an integer.
   * </p>
   * <p>
   * The return value is cast to an integer.
   * @since 5.1.0
   */
  public function count()
  {
    return (int) parent::count();
  }

  /**
   * Runs a test and collects its result in a TestResult instance.
   *
   * @param TestResult $result
   *
   * @return TestResult|null
   */
  public function run(TestResult $result = null)
  {
    return parent::run($result);
  }
}