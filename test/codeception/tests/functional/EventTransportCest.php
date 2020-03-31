<?php

class EventTransportCest
{

  public function _fixtures()
  {
    return require APP_TESTS_DIR . '/tests/fixtures/_combined_models.php';
  }

//  public function sendSimpleEvent(FunctionalTester $I)
//  {
//    $I->loginAnonymously();
//    $I->sendJsonRpcRequest('test','simple-event');
//    $I->assertSame( $I->getByJsonPath('$.result.type'), "ServiceResult");
//    $I->assertSame( $I->getByJsonPath('$.result.events[0].name'), "foo");
//    $I->assertSame( $I->getByJsonPath('$.result.events[0].data'), "Hello World");
//  }
//
//  public function tryAlertDialog(FunctionalTester $I)
//  {
//    $I->loginAnonymously();
//    $message = "Hello World!";
//    $I->sendJsonRpcRequest('test','alert', [$message]);
//    $I->assertSame( $I->getByJsonPath('$.result.events[0].name'), "dialog");
//    $I->assertSame( $I->getByJsonPath('$.result.events[0].data.type'), "alert");
//    $I->assertSame( $I->getByJsonPath('$.result.events[0].data.properties.message'), $message);
//  }

}
