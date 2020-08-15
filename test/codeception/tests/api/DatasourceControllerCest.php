<?php

class DatasourceControllerCest
{
  public function tryCreateDatasource(ApiTester $I)
  {
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('datasource','create', ["test123"]);
    $I->logout();
  }
}
