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
   * The properties that will be set on the widget
   * @var array
   */
  protected $properties = [];
  public function setProperties(array $value){$this->properties = $value; return $this;}

  /**
   * The caption of the dialog
   * @var string|null
   */
  protected $caption = null;
  public function setCaption($value){$this->caption = $value; return $this;}

  /**
   * Whether to show or hide the dialog widget
   * @var bool
   */
  protected $show = true;
  public function setShow(bool $value){$this->show = $value; return $this; }

  /**
   * The callback service (=>controller id)
   * @var string
   */
  protected $service;
  public function setService(string $value){$this->service = $value; return $this;}


  /**
   * The callback method (=>controller action id)
   * @var string
   */
  protected $method;
  public function setMethod(string $value){$this->method=$value;return $this;}

  /**
   * The parameters passed to the method/action
   * @var array
   */
  protected $params = [];
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
   * @param array $properties The name of the properties that are to be sent in the
   * `properties` object
   * @return $this
   */
  public function sendToClient(array $properties=[])
  {
    if (count($properties)) {
      foreach ($properties as $key) {
        if ($this->$key !== null) {
          $this->properties[$key] = $this->$key;
        }
      }
    }
    static::addToEventQueue( array(
      'type' => lcfirst((new \ReflectionClass($this))->getShortName()),
      'properties' => $this->properties,
      'service' => $this->service,
      'method'  => $this->method,
      'params'  => $this->params,
      'show'    => $this->show
    ));
    return $this;
  }

  /**
   * Shows the widget on the client
   * Alias of #sendToClient()
   */
  public function show()
  {
    return $this->sendToClient();
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

  /**
   * Hides the widget. Does not require #sendToClient();
   */
  public function hide() {
    $this->setShow(false);
    $this->sendToClient();
  }
}
