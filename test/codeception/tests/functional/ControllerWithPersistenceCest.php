<?php

class ControllerWithPersistenceCest
{

  public function _fixtures()
  {
    return require APP_TESTS_DIR . '/tests/fixtures/_access_models.php';
  }

  protected $testValues =  ["hello world", [ "one" => 1, "two" => [1,2,3] ]];

  public function tryTestPersistence(FunctionalTester $I)
  {
    $I->loginAnonymously();
    $I->amGoingTo("store values in the session");
    $I->sendJsonRpcRequest("test", "test-persistence", $this->testValues);
    $I->amGoingTo("check if the values were correctly stored and retrieved");
    $I->sendJsonRpcRequest("test", "test-persistence");
    $I->assertEquals($this->testValues, $I->grabJsonRpcResult());
  }
}
