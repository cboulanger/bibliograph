<?php

namespace app\tests\unit;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../_bootstrap.php";

/**
 * Undocumented class
 */
class Base extends \Codeception\Test\Unit
{
  static $migrationsApplied = false;

  static $migrationError = false;

  static $migrationOutput = null;

  /**
   * Run migrations
   *
   * @return void
   */
  public static function setUpBeforeClass(){
    // if( self::$migrationsApplied ) return;
    // $output = '';
    // $runner = new \toriphes\console\Runner();
    // $runner->run('migrate/fresh --interactive=0 --db=testdb -p=@app/migrations/schema' , $output);
    // self::$migrationsApplied = true;
    // self::$migrationOutput = $output;
    // if( strstr($output,"failed" )) self::$migrationError = true;
    // codecept_debug($output);
  }

  // public function testMigrations(){
  //   if( self::$migrationError == true ){
  //     $this->fail("Migrations failed: " . self::$migrationOutput );
  //   }
  // }
}