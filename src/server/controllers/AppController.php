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

use app\controllers\{traits\AuthTrait, traits\DatasourceTrait};
use app\models\Datasource;
use lib\dialog\Error;
use lib\exceptions\UserErrorException;
use Yii;
use app\models\Permission;
use yii\db\ActiveQuery;


/**
 * Service class providing methods to get or set configuration
 * values
 * @todo refactor to \lib\BaseController
 */
class AppController extends \JsonRpc2\Controller
{
  use AuthTrait;
  use DatasourceTrait;

  /**
   * Overridden to add functionality:
   * - catch User exception and convert it into an error dialog widget on the client
   * @inheritDoc
   */
  protected function _runAction($method)
  {
    try{
      return parent::_runAction($method);
    } catch ( UserErrorException $e ){
      Yii::info("User Error: " . $e->getMessage());
      Error::create($e->getMessage());
      return null;
    }
  }

  //-------------------------------------------------------------
  // Access control
  //-------------------------------------------------------------

  /**
   * Creates a permission with the given named id if it doesn't
   * already exist.
   * @param array|string $namedId The named id(s) of the permission(s)
   * @param string $description Optional description of the permission.
   *    Only used when first argument is a string.
   * @return void
   * @throws \yii\db\Exception
   */
  public function addPermission($namedId, $description = null)
  {
    if (is_array($namedId)) {
      foreach ($namedId as $id) {
        $this->addPermission( $id );
      }
      return;
    }
    $permission = new Permission([ 'namedId' => $namedId, 'description' => $description ]);
    $permission->save();
  }

  /**
   * Removes a permission with the given named id. Silently fails if the
   * permission doesn't exist.
   * @param array|string $namedId The named id(s) of the permission(s)
   * @return void
   */
  public function removePermission($namedId)
  {
    if (is_array($namedId)) {
      foreach ($namedId as $id) {
        $this->removePermission( $id );
      }
      return;
    }
    Permission::deleteAll(['namedId' => $namedId]);
  }

  //-------------------------------------------------------------
  // Datasources
  //-------------------------------------------------------------

  /**
   * Returns the query that belongs the model of the given type in the given datasource
   * @param string $datasource
   * @param string $type
   * @return ActiveQuery
   */
  protected function findIn( $datasource, $type ){
    return Datasource::getInstanceFor($datasource)->getClassFor($type)::find();
  }

  //-------------------------------------------------------------
  // Helpers for returning data to the user
  //-------------------------------------------------------------

  /**
   * Return a message that can be send as the result of an action which does
   * not return anything. The message is purely for diagnostic and debug reasons.
   * @param string|null $reason (optional) reason for the abort
   * @return string
   */
  public function successfulActionResult($reason=null)
  {
    return Yii::$app->requestedRoute . " was successful.";
  }

  /**
   * Return a message that can be send as the result of an action if this action
   * is aborted as response to user feedback. The message is purely for diagnostic
   * and debug reasons.
   * @param string|null $reason (optional) reason for cancelling
   * @return string
   */
  public function cancelledActionResult($reason=null)
  {
    return Yii::$app->requestedRoute . " was cancelled" . ($reason ? ": $reason." : ".");
  }

  /**
   * Return a message that can be send as the result of a failed action if this action
   * is aborted as response to user feedback without throwing an exception.
   * The message is purely for diagnostic and debug reasons.
   * @param string|null $reason (optional) reason for the abort
   * @return string
   */
  public function abortedActionResult($reason=null)
  {
    return "Aborted " . Yii::$app->requestedRoute . ($reason ? ": $reason." : ".");
  }

  //-------------------------------------------------------------
  // methods to pass data between service methods
  //-------------------------------------------------------------


  /**
   * Temporarily stores the supplied arguments on the server for retrieval
   * by another service method. This storage is only guaranteed to last during
   * the current session and is then discarded. The method can take a variable
   * number of arguments
   * @return string
   *    The shelf id needed to retrieve the data later
   */
  public function shelve()
  {
    $shelfId = Yii::$app->security->generateRandomString();
    Yii::$app->session->set($shelfId,func_get_args());
    //$_SESSION[$shelfId] = func_get_args();
    return $shelfId;
  }

  /**
   * Retrieve the data stored by the shelve() method.
   * @param $shelfId
   *    The id of the shelved data
   * @param bool $keepCopy
   *    If true, the data will be preserved and can be retrieved again.
   *    If false or omitted, the data will be deleted.
   * @return array
   *    Returns an array of the elements passed to the shelve() method, which can be
   *    extracted with the list() method.
   */
  public function unshelve($shelfId, $keepCopy=false )
  {
    $args =  Yii::$app->session->get($shelfId);
    //$args = $_SESSION[$shelfId];
    if ( !$keepCopy ) {
      //unset( $_SESSION[$shelfId] );
      Yii::$app->session->remove( $shelfId );
    }
    return $args;
  }

  /**
   * Returns true if something is stored und the shelf id
   * @param string $shelfId
   * @return bool
   */
  public function hasInShelf( $shelfId ){
    if( empty($shelfId) ) return false;
    return Yii::$app->session->has( $shelfId );
  }

  //-------------------------------------------------------------
  // send and broadcast messages
  // @todo reimplement and move into component
  //-------------------------------------------------------------

  /**
   * Broadcasts a message to all connected clients.
   * NOTE this doesn't work at the moment, the message is only sent to the 
   * current user's client. 
   * @todo Reimplement
   * @param string $eventName
   * @param mixed $data
   * @return void
   */
  public function broadcastClientMessage($eventName, $data=null){
    $this->dispatchClientMessage($eventName, $data);
  }

  /**
   * Sends a message to the current user's application
   * @param [type] $eventName
   * @param [type] $data
   * @return void
   */
  public function  dispatchClientMessage($eventName, $data=null){
    Yii::$app->eventQueue->add( new \yii\base\Event([
      "name" => $eventName,
      "data" => $data
    ]));
  }
}
