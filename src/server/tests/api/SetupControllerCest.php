<?php

class SetupControllerCest
{
  public function _fixtures()
  {
    return require __DIR__ . '/../fixtures/_access_models.php';
  }

  public function trySetup(ApiTester $I)
  {
    $I->sendJsonRpcRequest('setup','setup');
  }
}
