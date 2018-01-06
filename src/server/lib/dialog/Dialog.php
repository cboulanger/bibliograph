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
 *
 */
class Dialog extends \yii\base\BaseObject
{
  const EVENT_DIALOG = "dialog";

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
