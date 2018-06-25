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

use yii\base\Event;

/**
 * Base class for dialog data
 * @property string $type
 * @property array $properties
 * @property string $service
 * @property string $method
 * @property string $params
 *
 */
class Dialog extends \yii\base\BaseObject
{
  const EVENT_DIALOG = "dialog";

  /**
   * The type of the dialog widget
   * @var string
   */
  public $type;
  public function setType(string $value){$this->type = $value; return $this; }

  /**
   * The properties that will be set on the widget
   * @var array
   */
  public $properties = [];
  public function setProperties(array $value){$this->properties = $value; return $this;}


  /**
   * The callback service (=>controller id)
   * @var string
   */
  public $service;
  public function setService(string $value){$this->service = $value; return $this;}


  /**
   * The callback method (=>controller action id)
   * @var string
   */
  public $method;
  public function setMethod(string $value){$this->method=$value;return $this;}

  /**
   * The parameters passed to the method/action
   * @var array
   */
  public $params = [];
  public function setParams(array $value){$this->params=$value; return $this;}


  /**
   * Shorthand method for Yii controllers which will set
   * 'service' and 'method' properties automatically
   * @param $route
   * @return $this
   */
  public function setRoute($route){
    $parts = explode("/", $route);
    if(count($parts)===1) $parts[] = "index";
    $this->service = implode("/", array_slice($parts, 0,-1));
    $this->method = $parts[count($parts)-1];
    return $this;
  }

  /**
   * Sends the dialog data to the client
   */
  public function sendToClient()
  {
    static::createWidget(
      $this->type,
      $this->properties,
      $this->service,
      $this->method,
      $this->params
    );
  }

  /**
   * Returns an event to the client which shows a widget of the given type, having the
   * given properties
   * @param string $type
   *    The type of the dialog widget
   * @param array|null $properties
   *    If array, populate the properties of the widget with the key-value pairs
   * @param string $callbackService
   *    The name of the service to be called
   * @param string $callbackMethod
   *    The name of the method to be called
   * @param array|null $callbackParams
   *    The parameters to be passed to the service
   */
  public static function createWidget( $type, array $properties, $callbackService, $callbackMethod, $callbackParams=array() )
  {
    static::addToEventQueue( array(
      'type'        => $type,
      'properties'  => $properties,
      'service'     => $callbackService,
      'method'      => $callbackMethod,
      'params'      => $callbackParams
    ));
  }

  /**
   * The data of the message that triggers the display of the dialog widget
   * @param $data
   */
  protected static function addToEventQueue( $data )
  {
    $event = new Event([ 'name' => Dialog::EVENT_DIALOG, 'data' => $data ]);
    \Yii::$app->eventQueue->add( $event );
  }
}
