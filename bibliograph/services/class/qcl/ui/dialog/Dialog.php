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

qcl_import("qcl_data_Result");

/**
 * Base class for dialog data
 *
 */
class qcl_ui_dialog_Dialog
  extends qcl_data_Result
{

  /**
   * Default constructor to be overridden by subclasses
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
  function __construct( $type, array $properties, $callbackService, $callbackMethod, $callbackParams=array() )
  {
    $this->dispatchDialogMessage( array(
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
  function dispatchDialogMessage( $data )
  {
    /*
     * only dispatch the message if event transport is on
     */
    if ( $this->getApplication()->getIniValue("service.event_transport") )
    {
      $this->getMessageBus()->dispatchClientMessage(
        null, "qcl.ui.dialog.Dialog.createDialog", $data
      );
    }
    else
    {
      $this->warn( "Cannot dispatch message - event transport is off!");
    }
  }
}
