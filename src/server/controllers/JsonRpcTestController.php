<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
   2007-2017 Christian Boulanger

   License:
   LGPL: http://www.gnu.org/licenses/lgpl.html
   EPL: http://www.eclipse.org/org/documents/epl-v10.php
   See the LICENSE file in the project's top-level directory for details.

   Authors:
   * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use app\models\Datasource;
use app\models\Folder;
use app\models\Reference;
use app\modules\z3950\models\Search;
use lib\dialog\Alert;
use lib\exceptions\UserErrorException;
use Yii;
use lib\channel\Channel;
use georgique\yii2\jsonrpc\Controller as JsonRpcController;

/**
 * A test suite for the JSON-RPC client/server setup
 */
class JsonRpcTestController extends AppController
{
  /**
   * @inheritDoc
   *
   * @var array
   */
  protected $noAuthActions = ["echo*", "notify-me"];

  /**
   * Returns the first argument passed unchanged
   * @param mixed $value
   * @return mixed
   */
  public function actionEcho($value) {
    return $value;
  }

  /**
   * Returns the first argument passed unchanged, which must be an array
   * See https://github.com/yiisoft/yii2/issues/17955
   * @param array $value
   * @return mixed
   */
  public function actionEchoArray(array $value) {
    return $value;
  }

  /**
   * Sends a notification to the method of a specially marked singleton instance
   * @param $value
   * @return string
   */
  public function actionNotifyMe($value) {
    JsonRpcController::addNotification("bibliograph.test.RemoteProcedure.receiveNotification", [$value]);
    return "OK";
  }



}
