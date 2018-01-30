<?php

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../_bootstrap.php';

class ReferenceControllerCest
{

  public function _fixtures()
  {
    return require __DIR__ . '/../fixtures/_combined_models.php';
  }

  protected function getQueryData()
  {
    return json_decode('{
      "datasource" : "database1",
      "modelType" : "reference",
      "query" : {
        "properties" : ["author","title","year"],
        "orderBy" : "author",
        "relation" : {
          "name" : "folders",
          "foreignId" : "FolderId",
          "id" : 3
        }
      }
    }',true);
  }

  public function tryRowCount(FunctionalTester $I)
  {
    $I->loginAnonymously();
    $I->sendJsonRpcRequest('reference','row-count', [$this->getQueryData()]);
    //codecept_debug($I->grabResponse());
    $rowCount = (int) $I->grabDataFromResponseByJsonPath('$.result.rowCount')[0];
    $I->assertSame( $rowCount, 7 );
  }

  public function tryRowData(FunctionalTester $I)
  {
    $I->loginAnonymously();
    $I->sendJsonRpcRequest('reference','row-data', [0,50,0,$this->getQueryData()]);
    //codecept_debug($I->grabResponse());
    $rowData = $I->grabDataFromResponseByJsonPath('$.result.rowData')[0];
    $I->assertSame( count($rowData), 7);
    $I->assertSame( $rowData[0]['author'], "Bennett, Frank G.");
  }  

}
