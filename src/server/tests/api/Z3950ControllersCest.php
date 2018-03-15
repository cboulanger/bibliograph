<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 12.03.18
 * Time: 15:22
 */

namespace tests\api;

use Yii;
use React\EventLoop\StreamSelectLoop;
use React\Stream\ReadableResourceStream;
use yii\web\Request;

class Z3950ControllersCest
{

  /**
   * @param \ApiTester $I
   */
  public function tryToStartApp(\ApiTester $I)
  {
    $I->sendJsonRpcRequest("setup","setup");
    $I->loginWithPassword('admin','admin');
  }

  /**
   * @param \ApiTester $I
   */
  public function tryToInvokeModuleEvents(\ApiTester $I)
  {
    $I->sendJsonRpcRequest('test','create-search');
    $I->sendJsonRpcRequest('test','retrieve-search');
    $I->compareJsonRpcResultWith("foo","query");
    $I->logout();
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('test','retrieve-search');
    $I->compareJsonRpcResultWith(null);
  }

  /**
   * @param \ApiTester $I
   */
  public function tryToGetServerList(\ApiTester $I)
  {
    $I->sendJsonRpcRequest('z3950/table','list-servers');
    $I->seeResponseMatchesJsonType([
      'label' => 'string',
      'value' => 'string',
      'active' => 'boolean',
      'selected' => 'boolean'],
      '$.result[0]'
    );
  }

  /**
   * @param \ApiTester $I
   */
  public function tryToLoadIndexPage(\ApiTester $I)
  {
    $I->sendGET("/z3950/search");
    $I->seeResponseContains("nothing here");
  }

  /**
   * @param \ApiTester $I
   */
  public function tryToExecuteSearch(\ApiTester $I)
  {
    codecept_debug($I->getBaseUrl());
    return;
    $loop = new StreamSelectLoop;

    $source = new ReadableResourceStream(fopen('source.txt', 'r'), $loop);

    $loop->run();
    $loop->stop();

  }


}