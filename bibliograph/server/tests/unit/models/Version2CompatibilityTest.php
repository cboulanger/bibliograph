<?php

namespace app\tests\unit\models;

use app\models\User;
use app\models\Group;
use app\models\Permission;
use app\models\Role;
use app\models\Config;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php"; 

class Version2CompatibilityTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures(){
      return require 'fixture_data_v2.php';
    }

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testFindUser()
    {
      $I = $this->tester;
      $I->wantToTest("User data");
      $user = User::findOne(['namedId'=>"sarah_manning"]);
      $this->assertEquals('Sarah Manning', $user->name );
    }

    public function testGroupNames()
    {
      $user = User::findOne(['name'=>"Dale Cooper"]);
      $this->assertEquals('dale_cooper@bibliograph.org', $user->email );
      $groupNames = $user->getGroupNames();
      $this->assertEquals(['group2','group3'], $groupNames );
    }

    public function testGlobalRoles()
    {
      $user = User::findOne(['name'=>"Frank Underwood"]);
      $groupNames = $user->getRoleNames();
      $this->assertEquals(['manager'], $groupNames );
    } 

    public function testGroupRoles()
    {
      $user = User::findOne(['name'=>"Dale Cooper"]);
      $group = Group::findOne(['namedId'=>'group2']);
      $groupNames = $user->getRoleNames($group->id);
      $this->assertEquals(['manager'], $groupNames );
    }

    public function testConfigData()
    {
      $I = $this->tester;
      $I->wantToTest("Configuration data");
      $config = Config::findOne(['namedId'=>'application.title']);
      $this->assertEquals('Bibliograph Online Bibliographic Data Manager', $config->default );
    }
}
