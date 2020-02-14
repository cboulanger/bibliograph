<?php


class UtilsCest
{

    // tests
    public function tryToShelveAndUnshelveData(ApiTester $I)
    {
      $I->loginAsAdmin();
      $I->amGoingTo("store data on the client and get a shelf id in response");
      $obj = new StdClass();
      $obj->baz = "boo";
      $data = [
        "foo",
        [1,2,3,4,5],
        (array) $obj
      ];
      $I->sendJsonRpcRequest("test","shelve", [ json_encode($data)] );
      //$I->sendJsonRpcRequest("test","shelve", $data );
      $shelfId = $I->grabRpcData();
      $I->assertTrue(!is_null($shelfId) and is_string($shelfId), "Invalid shelf id '$shelfId' returned");
      $I->amGoingTo("check if the stored data is returned properly");
      $I->sendJsonRpcRequest("test","unshelve",[$shelfId]);
      $received = $I->grabRpcData();
      $I->compareRpcDatatWith($data);
    }
}
