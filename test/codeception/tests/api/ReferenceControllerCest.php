<?php


class ReferenceControllerCest
{

  protected function getQueryData()
  {
    return json_decode('{
      "datasource" : "datasourceForApiTest",
      "modelType" : "reference",
      "query" : {
        "properties" : ["author","title","year"],
        "orderBy" : "author",
        "relation" : {
          "name" : "folders",
          "foreignId" : "FolderId",
          "id" : 1
        }
      }
    }',true);
  }


  public function tryCreateDatasourceAndReferences(ApiTester $I){
    $datasourceName = $this->getQueryData()['datasource'];
    $I->loginAsAdmin();
    $I->amGoingTo("call the setup method");
    $I->sendJsonRpcRequest("setup","setup");
    $I->amGoingTo("create a datasource '$datasourceName'");
    $I->sendJsonRpcRequest("datasource", "create",[$datasourceName]);
    $I->amGoingTo("create a book record");
    $I->sendJsonRpcRequest("reference","create",[$datasourceName,1,[
      'reftype' => "book",
      'author'=>"Doe, John",
      'year'=> "2000",
      'title'=>"Book Title 1",
    ]]);
  }

  public function tryRowCount(ApiTester $I)
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('reference','row-count', [$this->getQueryData()]);
    $rowCount = (int) $I->grabRequestedResponseByJsonPath('$.result.rowCount')[0];
    $I->assertSame( $rowCount, 1 );
  }

  public function tryRowData(ApiTester $I, $scenario )
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('reference','row-data', [0,50,0,$this->getQueryData()]);
    $rowData = $I->grabRequestedResponseByJsonPath('$.result.rowData')[0];
    $I->assertSame( count($rowData), 1);
    $I->assertSame( $rowData[0]['author'], "Doe, John");
  }
}
