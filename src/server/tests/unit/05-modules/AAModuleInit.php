<?php

namespace app\tests\unit\models;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php";

use Yii;
use lib\dialog\{Alert,Confirm,Form,Popup,Progress,Prompt,RemoteWizard,Select,Wizard};

class AAModuleInit extends \app\tests\unit\Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;



}