<?php

namespace app\tests\unit\models;

use app\models\User;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php"; 

class AccessTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures(){
      return require 'fixtures.php';
    }

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testFindingUser()
    {
      $user = User::findOne(['namedId'=>"sarah_manning"]);
      $this->assertEquals('Sarah Manning', $user->name );
    }
}
