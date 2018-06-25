<?php

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../_bootstrap.php';

class EventTransportCest
{

  public function _fixtures()
  {
    return require __DIR__ . '/../fixtures/_combined_models.php';
  }

  public function trySimpleEvent(FunctionalTester $I)
  {
    $I->loginAnonymously();
    $I->sendJsonRpcRequest('test','simple-event');
    $I->assertSame( $I->getByJsonPath('$.result.type'), "ServiceResult");
    $I->assertSame( $I->getByJsonPath('$.result.events[0].name'), "foo");
    $I->assertSame( $I->getByJsonPath('$.result.events[0].data'), "Hello World");
  }  

  public function tryAlertDialog(FunctionalTester $I)
  {
    $I->loginAnonymously();
    $message = "Hello World!";
    $I->sendJsonRpcRequest('test','alert', [$message]);
    $I->assertSame( $I->getByJsonPath('$.result.events[0].name'), "dialog");
    $I->assertSame( $I->getByJsonPath('$.result.events[0].data.type'), "alert");
    $I->assertSame( $I->getByJsonPath('$.result.events[0].data.properties.message'), $message);
  }

}
