<?php

namespace app\tests\unit\models;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php";

/**
 * Undocumented class
 */
class Base extends \Codeception\Test\Unit
{
  static $migrationsApplied = false;

  /**
   * Run migrations
   *
   * @return void
   */
  public static function setUpBeforeClass(){
    if( self::$migrationsApplied ) return;
    $output = '';
    $runner = new \toriphes\console\Runner();
    $runner->run('migrate/up --interactive=0 --migrationPath=@tests' , $output);
    self::$migrationsApplied = true;
    codecept_debug($output);
  }

}