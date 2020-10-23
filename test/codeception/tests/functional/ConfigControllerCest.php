<?php

class ConfigControllerCest
{

  public function _fixtures()
  {
    return require APP_TESTS_DIR . '/tests/fixtures/_access_models.php';
  }

  public function tryLoadConfigAnonymously(FunctionalTester $I)
  {
    $I->loginAnonymously();
    $I->sendJsonRpcRequest('config','load');
    $I->seeRequestedResponseMatchesJsonPath('$.result.keys');
    $I->seeRequestedResponseMatchesJsonPath('$.result.values');
    $I->seeRequestedResponseMatchesJsonPath('$.result.types');
    $keys = $I->grabRequestedResponseByJsonPath('$.result.keys')[0];
    $I->assertEquals(count($keys),12);
  }

  public function tryLoadConfigAuthenticated(FunctionalTester $I)
  {
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('config','load');
    $keys = $I->grabRequestedResponseByJsonPath('$.result.keys')[0];
    $I->assertEquals(count($keys),12);
  }

  public function trySetConfig(FunctionalTester $I)
  {
    $I->amGoingTo("login in as Sarah Manning, who is not an admin");
    $I->loginWithPassword('sarah_manning','sarah_manning');
    $I->amGoingTo("try to set the application title, which I am not allowed to, and which should fail.");
    $I->sendJsonRpcRequest('config','set', ['application.title','Ha! I shouldn\'t be allowed to change the application title!'], true);
    $I->seeJsonRpcError("Not allowed");
    $I->amGoingTo("change a config setting");
    $I->sendJsonRpcRequest('config','set', ['csl.style.default','APA'], true);
    $I->dontSeeJsonRpcError();
    $I->logout();

    $I->amGoingTo("login as admin and do the same thing successfully.");
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('config','set', ['application.title','New application title']);
    $I->dontSeeJsonRpcError();
    $I->amGoingTo("change a config setting default value");
    $I->sendJsonRpcRequest('config','set', ['application.locale','de_CH']);
    $I->dontSeeJsonRpcError();
    $I->logout();

    $I->amGoingTo("login anonymously and check that I am getting the default value for a config setting");
    $I->loginAnonymously();
    $I->sendJsonRpcRequest('config','get', ['csl.style.default']);
    $I->assertSame( $I->grabJsonRpcResult(), 'chicago-author-date' );
    $I->logout();

    $I->amGoingTo("login as Sarah Manning again and see if my change has been correctly saved");
    $I->loginWithPassword('sarah_manning','sarah_manning');
    $I->sendJsonRpcRequest('config','get', ['csl.style.default']);
    $I->assertSame( $I->grabJsonRpcResult(), "APA" );
  }
}
