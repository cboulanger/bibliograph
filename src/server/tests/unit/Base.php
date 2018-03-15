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

  //static $migrationsApplied = false;

  //static $migrationError = false;

  //static $migrationOutput = null;

  /**
   * Run migrations
   *
   * @return void
   */
  //public static function setUpBeforeClass(){
    // if( self::$migrationsApplied ) return;
    // $output = '';
    // $runner = new \toriphes\console\Runner();
    // $runner->run('migrate/fresh --interactive=0 --db=testdb -p=@app/migrations/schema' , $output);
    // self::$migrationsApplied = true;
    // self::$migrationOutput = $output;
    // if( strstr($output,"failed" )) self::$migrationError = true;
    // codecept_debug($output);
  //}

  // public function testMigrations(){
  //   if( self::$migrationError == true ){
  //     $this->fail("Migrations failed: " . self::$migrationOutput );
  //   }
  // }


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
    return parent::count();
  }

  /**
   * Runs a test and collects its result in a TestResult instance.
   *
   * @param TestResult $result
   *
   * @return TestResult
   */
  public function run(TestResult $result = null)
  {
    return parent::run($result);
  }
}