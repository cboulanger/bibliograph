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

namespace lib\dialog;

/**
 * Class Alert
 * @package lib\dialog
 * @property string $message
 */
class Alert extends Dialog
{

  /**
   * @var string
   */
  protected $message = "";

  /**
   * @param $value
   * @return $this
   */
  public function setMessage(string $value){$this->message=$value; return $this;}

  /**
   * @var string
   */
  protected $image = "dialog.icon.alert";

  /**
   * @param $value
   * @return $this
   */
  public function setImage(string $value){$this->image=$value; return $this;}

  /**
   * @inheritDoc
   */
  public function sendToClient(array $properties=[])
  {
    return parent::sendToClient(array_merge($properties,['message','image']));
  }

  /**
   * Returns a message to the client which prompts the user with an alert message
   * @param string $message
   *    The message text
   * @param string $callbackService
   *    Optional service that will be called when the user clicks on the OK button
   * @param string $callbackMethod
   *    Optional service method
   * @param array $callbackParams
   *    Optional service params
   * @deprecated Please use setters instead
   */
  public static function create()
  {
    list(
      $message,
      $callbackService,
      $callbackMethod,
      $callbackParams
      ) = array_pad( func_get_args(), 4, null);

    static::addToEventQueue( array(
     'type' => "alert",
     'properties' => array(
        'message' => $message
      ),
     'service' => $callbackService,
     'method'  => $callbackMethod,
     'params'  => $callbackParams ?? []
    ));
  }
}
