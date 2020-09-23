<?php

namespace tests\unit\models;

use tests\unit\Base;
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
    return require APP_TESTS_DIR . '/tests/fixtures/_access_models.php';
  }

  public function testConfigData()
  {
    $config = Config::findOne(['namedId'=>'application.locale']);
    $this->assertEquals('en', $config->default );
    $user = User::findOne(['namedId'=>'admin']);
    $this->assertEquals('de', $config->getUserConfigValue($user->id) );
  }
}
