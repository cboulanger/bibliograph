<?php

namespace app\tests\unit\models;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php";

use app\tests\unit\Base;
use app\models\User;
use app\models\Group;
use app\models\Permission;
use app\models\Role;
use app\models\Config;
use app\models\UserConfig;

class ConfigModelTest extends Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  public function _fixtures(){
    return require __DIR__ . '/../../fixtures/_access_models.php';
  }    

  public function testConfigData()
  {
    $config = Config::findOne(['namedId'=>'application.locale']);
    $this->assertEquals('en', $config->default );
    $user = User::findOne(['namedId'=>'admin']);
    $this->assertEquals('de', $config->getUserConfigValue($user->id) );
  }
}
