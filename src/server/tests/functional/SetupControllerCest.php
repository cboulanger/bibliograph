<?php

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../_bootstrap.php';

class SetupControllerCest
{

  public function _fixtures()
  {
    return require __DIR__ . '/../fixtures/_access_models.php';
  }

  public function trySetup(FunctionalTester $I)
  {
    $I->sendJsonRpcRequest('setup','setup');
    
  }
 }
