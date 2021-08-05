<?php

class AAAPhpCest
{
  public function tryPhpVersion(FunctionalTester $I)
  {
    $expected_php_version = $_SERVER['PHP_VERSION'];
    $I->expectTo("find PHP version $expected_php_version");
    $actual_php_version = PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
    $I->assertTrue($expected_php_version === $actual_php_version,"Wrong PHP version $actual_php_version, expected $expected_php_version");
  }
}
