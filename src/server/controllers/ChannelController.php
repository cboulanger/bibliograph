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

use \JsonRpc2\extensions\AuthException;

use app\controllers\AppController;

use app\models\User;
use app\models\Session;
use app\models\Message;

use lib\channel\Channel;
use lib\channel\MessageEvent;
use lib\channel\Aggregator;


/**
 * The controller for PubSub communication
 */
class ChannelController extends AppController
{


  //-------------------------------------------------------------
  // Actions / JSONRPC API
  //-------------------------------------------------------------  
  
  public function actionSend( $name, $data ) 
  {
    $message = new MessageEvent(['name'=>$name, 'data'=>$data]);
    Yii::$app->trigger('message', $message);
    return $this->actionFetch($name);
  }

  public function actionBroadcast( $name, $data )
  {
    $sessionId = Yii::$app->session->getId();
    $channel = new Channel( $name, $sessionId );
    $channel->broadcast($data);
    // remove the message to self and send a message event instead
    $message = Message::findOne(['SessionId'=>$sessionId]);
    if($message) $message->delete(); 
    return $this->actionSend( $name, $data );
  }

  public function actionFetch()
  {
    $channel = new Aggregator( Yii::$app->session->getId() );
    if( $channel->check() ){
      return $channel->update();
    }
  }
}