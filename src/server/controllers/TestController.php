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

use Yii;

use app\controllers\AppController;

use app\models\User;
use app\models\Role;
use app\models\Permission;
use app\models\Group;
use app\models\Session;
use app\models\Message;
use lib\channel\Channel;

/**
 * A controller for JSONRPC methods intended to test the application.
 */
class TestController extends AppController
{
  public function actionError()
  {
    $exception = Yii::$app->errorHandler->exception;
    return [ "message" => $exception ];
  }

  public function actionTest()
  {
    throw new \lib\exceptions\UserErrorException("This is a user error");
    //\lib\dialog\Alert::create("It works!","test","test2",["it really does."]);
  }

  public function actionTest2($result, $message )
  {
    \lib\dialog\Alert::create($message);
  }  

  public function create_messages($sessionId)
  {
    $channel = new Channel('test', $sessionId);
    for ($i=0; $i < 10 ; $i++) { 
      $channel->send( "The time is " . date('l, F jS, Y, h:i:s A'));
    }
    $channel->send("done");
  }

  public function actionAlert( $message )
  {
    \lib\dialog\Alert::create( $message );
  }

  public function actionSimpleEvent()
  {
    $this->dispatchClientMessage("foo","Hello World");
  }

}