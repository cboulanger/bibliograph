<?php

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../_bootstrap.php';

class DatasourceControllerCest
{

  public function _fixtures()
  {
    return require __DIR__ . '/../fixtures/_combined_models.php';
  }

  public function tryCreateDatasource(FunctionalTester $I)
  {
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('datasource','create', ["test123"]);
  }
}
