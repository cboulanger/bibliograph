<?php


class ReferenceControllerCest
{

  public function _fixtures()
  {
    return require APP_TESTS_DIR . '/tests/fixtures/_combined_models.php';
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
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('reference','row-count', [$this->getQueryData()]);
    codecept_debug($I->grabResponse());
    $rowCount = (int) $I->grabDataFromResponseByJsonPath('$.result.rowCount')[0];
    $I->assertSame( 3, $rowCount );
  }

  public function tryRowData(FunctionalTester $I, $scenario )
  {
    $I->loginAsAdmin();
    try{
      $I->sendJsonRpcRequest('reference','row-data', [0,50,0,$this->getQueryData()]);
      //codecept_debug($I->grabResponse());
      $rowData = $I->grabDataFromResponseByJsonPath('$.result.rowData')[0];
      $I->assertSame( count($rowData), 3);
      $I->assertSame( 'Balmisse, Gilles; Meingan, Denis; Passerini, Katia', $rowData[0]['author']);
    } catch( \yii\base\InvalidParamException $e){
      $scenario->skip("Travis UTF-8 problem...");
    }
  }
}
