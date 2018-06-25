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

class Login extends Dialog
{
  /**
   * Returns a message to the client which prompts the user with an login dialog
   * @param string $message 
   *    The message text
   * @param string $callbackService 
   *    Optional service that will be called when the user clicks on the OK button
   * @param string $callbackMethod 
   *    Optional service method
   * @param array $callbackParams 
   *    Optional service params
   */
  public static function create( $message, $callbackService=null, $callbackMethod=null, array $callbackParams=[] )
  {
    static::addToEventQueue( array(
     'type' => "login",
     'properties' => array(
        'text' => $message
      ),
     'service' => $callbackService,
     'method'  => $callbackMethod,
     'params'  => $callbackParams
    ));
  }
}
