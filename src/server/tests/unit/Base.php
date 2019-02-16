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
}
