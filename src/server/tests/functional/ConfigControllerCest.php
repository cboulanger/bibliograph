<?php

// for whatever reason, this is not loaded early enough
require_once __DIR__ . '/../_bootstrap.php';

class ConfigControllerCest
{

  public function _fixtures()
  {
    return require __DIR__ . '/../fixtures/_access_models.php';
  }

  public function tryLoadConfigAnonymously(FunctionalTester $I)
  {
    $I->loginAnonymously();
    $I->sendJsonRpcRequest('config','load');
    $I->seeResponseJsonMatchesJsonPath('$.result.keys');
    $I->seeResponseJsonMatchesJsonPath('$.result.values');
    $I->seeResponseJsonMatchesJsonPath('$.result.types');
    $keys = $I->grabDataFromResponseByJsonPath('$.result.keys')[0];
    $I->assertEquals(count($keys),12);
  }
 
  public function tryLoadConfigAuthenticated(FunctionalTester $I)
  {
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('config','load');
    $keys = $I->grabDataFromResponseByJsonPath('$.result.keys')[0];
    $I->assertEquals(count($keys),12);
  }

  public function trySetConfig(FunctionalTester $I)
  {
    $I->loginWithPassword('sarah_manning','sarah_manning');
    $I->sendJsonRpcRequest('config','set', ['application.title','Ha! I shouldn\'t be allowed to change the application title!'], true);
    $I->seeJsonRpcError("Not allowed");
    $I->sendJsonRpcRequest('config','set', ['csl.style.default','APA'], true);
    $I->dontSeeJsonRpcError();
    $I->logout();
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('config','set', ['application.title','New application title']);
    $I->dontSeeJsonRpcError();
    $I->sendJsonRpcRequest('config','set', ['application.locale','de_CH']);
    $I->dontSeeJsonRpcError();    
    $I->loginAnonymously();
    $I->sendJsonRpcRequest('config','get', ['csl.style.default']);
    $I->assertSame( $I->grabJsonRpcResult(), 'chicago-author-date' );
    $I->logout();
    $I->loginWithPassword('sarah_manning','sarah_manning');
    $I->sendJsonRpcRequest('config','get', ['csl.style.default']);
    $I->assertSame( $I->grabJsonRpcResult(), "APA" );
  }
}
