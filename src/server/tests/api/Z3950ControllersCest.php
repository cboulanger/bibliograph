<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 12.03.18
 * Time: 15:22
 */

namespace tests\api;


class Z3950ControllersCest
{
  /**
   * @param \ApiTester $I
   */
  public function tryToInvokeModuleEvents(\ApiTester $I)
  {
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('test','create-search');
    $I->sendJsonRpcRequest('test','retrieve-search');
    $I->compareJsonRpcResultWith("foo","query");
    $I->logout();
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('test','retrieve-search');
    $I->compareJsonRpcResultWith(null);
  }
}