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

class Select extends Dialog
{

  /**
   * Returns an event to the client which prompts the user with a choice of options.
   *
   * @param string $message 
   *    The message text
   * @param array $options 
   *    Arrray containing maps of button data with the keys "label", "value", "icon"
   * @param bool $allowCancel
   * @param string $callbackService 
   *    Service that will be called when the user clicks on the selected button
   * @param string $callbackMethod 
   *    Service method
   * @param array $callbackParams 
   *    Optional service params
   */
  public static function create(
    $message,
    $options,
    $allowCancel = true,
    $callbackService,
    $callbackMethod,
    $callbackParams = null
  ) {
    static::addToEventQueue( array(
    'type' => "select",
    'properties' => array(
      'message'     => $message,
      'options'     => $options,
      'allowCancel' => $allowCancel
     ),
    'service' => $callbackService,
    'method'  => $callbackMethod,
    'params'  => $callbackParams
    ));
  }
}
