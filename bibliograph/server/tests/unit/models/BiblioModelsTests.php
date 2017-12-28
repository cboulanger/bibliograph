<?php

namespace app\tests\unit\models;

use app\models\User;
use app\models\Group;
use app\models\Permission;
use app\models\Role;
use app\models\Config;
use app\models\UserConfig;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php"; 

class BiblioModelsTests extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures(){
      return require '../../fixtures/_biblio_models.php';
    }

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    

}
