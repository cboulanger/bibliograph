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

class Wizard extends Dialog
{
  /**
   * Returns a message to the client which prompts the user with a wizard widget.
   *
   * @param array $pageData 
   *    Array containing the page data (see qcl.ui.dialog.Wizard#pageData)
   * @param bool $allowCancel
   *    Wheter the Wizard can be cancelled
   * @param string $callbackService 
   *    Service that will be called when the user clicks on the OK button
   * @param string $callbackMethod 
   *    Service method
   * @param array $callbackParams 
   *    Optional service params
   */
  public static function create(
    $pageData,
    $allowCancel=true,
    $callbackService,
    $callbackMethod,
    $callbackParams=null )
  {
    static::addToEventQueue( array(
       'type' => "wizard",
       'properties'  => array(
          'pageData'    => $pageData,
          'allowCancel' => $allowCancel
        ),
       'service' => $callbackService,
       'method'  => $callbackMethod,
       'params'  => $callbackParams
    ));
  }
}
