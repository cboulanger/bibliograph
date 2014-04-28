<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_ui_dialog_Dialog" );

class qcl_ui_dialog_Prompt
  extends qcl_ui_dialog_Dialog
{

  /**
   * Returns a message to the client which prompts the user with an message and
   * an input field.
   * @param string $message 
   *    The message text
   * @param string|null 
   *    The default value
   * @param string|null $callbackService 
   *    Optional service that will be called when the user clicks on the OK button
   * @param string|null $callbackMethod Optional service method
   * @param array|null $callbackParams 
   *    Optional service params
   * @param bool|null $requireInput 
   *    Optional flag to prevent user from submitting an empty response
   * @param number|null $autoSubmitTimeout 
   *    Optional timeout in seconds If provided, the prompt dialog "submits itself"
   *    after the given timeout. If the $requireInput flag is set to true, this
   *    happens only if input has been entered and this input hasn't changed 
   *    for the duration of the timeout
   * @return \qcl_ui_dialog_Prompt
   */
  function __construct( 
    $message, $value, $callbackService=null, 
    $callbackMethod=null, $callbackParams=null, 
    $requireInput=null, $autoSubmitTimeout=null )
  {
    $properties = array(
        'message'           => $message,
        'value'             => $value,
    );
    
    if ( $requireInput !== null ) $properties['requireInput'] = $requireInput;
    if ( $autoSubmitTimeout !== null ) $properties['autoSubmitTimeout'] = $autoSubmitTimeout;
      
    $this->dispatchDialogMessage( array(
      'type' => "prompt",
      'properties' => $properties,
      'service' => $callbackService,
      'method'  => $callbackMethod,
      'params'  => $callbackParams
    ));
  }
}
?>