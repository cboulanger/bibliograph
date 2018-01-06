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

class Form extends Dialog
{
  /**
   * Returns an event to the client which prompts the user with a form.
   *
   * @param string $message 
   *    The message text
   * @param array $formData 
   *    Arrray containing the form data. Example (using
   *    json instead of native php array):
   * <pre>
   * {
   *   'username' :
   *   {
   *     'type'  : "TextField",
   *     'label' : "User Name",
   *     'value' : ""
   *   },
   *   'address' :
   *   {
   *     'type'  : "TextArea",
   *     'label' : "Address",
   *     'lines' : 3
   *   },
   *   'domain'   :
   *   {
   *     'type'  : "SelectBox",
   *     'label' : "Domain",
   *     'value' : 1,
   *     'options' : [
   *       { 'label' : "Company", 'value' : 0 },
   *       { 'label' : "Home",    'value' : 1 }
   *     ]
   *   },
   *   'commands'   :
   *   {
   *    'type'  : "ComboBox",
   *     'label' : "Shell command to execute",
   *     'options' : [
   *       { 'label' : "ln -s *" },
   *       { 'label' : "rm -Rf /" }
   *     ]
   *   }
   * }
   * </pre>
   * @param bool $allowCancel
   *    Whether the form can be cancelled
   * @param string $callbackService 
   *    Service that will be called when the user clicks on the OK button
   * @param string $callbackMethod 
   *    Service method
   * @param array $callbackParams 
   *    Optional service params
   */
  public static function create(
    $message,
    $formData,
    $allowCancel=true,
    $callbackService,
    $callbackMethod,
    $callbackParams=null )
  {
    static::addToEventQueue( array(
       'type' => "form",
       'properties'  => array(
          'message'     => $message,
          'formData'    => $formData,
          'allowCancel' => $allowCancel,
          'maxWidth'    => 500 // FIXME Hardcoding this is BAD!
        ),
       'service' => $callbackService,
       'method'  => $callbackMethod,
       'params'  => $callbackParams
    ));
  }
}
