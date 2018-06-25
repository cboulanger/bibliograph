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

use app\controllers\traits\{
  AccessControlTrait, AuthTrait, DatasourceTrait, MessageTrait, ShelfTrait
};
use lib\dialog\Error;
use lib\exceptions\AccessDeniedException;
use lib\exceptions\UserErrorException;
use Yii;


/**
 * Service class providing methods to get or set configuration
 * values
 * @todo refactor to \lib\BaseController
 */
class AppController extends \JsonRpc2\Controller
{
  use AuthTrait;
  use MessageTrait;
  use DatasourceTrait;
  use ShelfTrait;
  use AccessControlTrait;

  /**
   * The category of this class
   */
  const CATEGORY = "app";

  /**
   * Overridden to add functionality:
   * - catch User exception and convert it into an error dialog widget on the client
   * @inheritDoc
   */
  protected function _runAction($method)
  {
    try{
      return parent::_runAction($method);
    } catch (UserErrorException $e){
      Yii::info("User Error: " . $e->getMessage());
      Error::create($e->getMessage());
      return null;
    } catch (AccessDeniedException $e){
      Yii::info("Access Denied: " . $e->getMessage());
    }
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

}
