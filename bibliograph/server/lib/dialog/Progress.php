<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
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

class qcl_ui_dialog_Progress
  extends qcl_ui_dialog_Dialog
{

  /**
   * Returns a message to the client which shows a progress dialog message
   * @param array|null $properties
   *    If array, populate the properties of the widget with the key-value pairs
   * @param string $callbackService
   *    The name of the service to be called
   * @param string $callbackMethod
   *    The name of the method to be called
   * @param array|null $callbackParams
   *    The parameters to be passed to the service
   * @return \qcl_ui_dialog_Progress
   */
  function __construct( $properties, $callbackService, $callbackMethod, $callbackParams )
  {
    parent::__construct("progress", $properties, $callbackService, $callbackMethod, $callbackParams );
  }
}