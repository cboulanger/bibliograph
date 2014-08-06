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

qcl_import("qcl_ui_dialog_Dialog");

class qcl_ui_dialog_Wizard
  extends qcl_ui_dialog_Dialog
{

  /**
   * Returns a message to the client which prompts the user with a wizard widget.
   *
   * @param array $pageData Array containing the page data (see qcl.ui.dialog.Wizard#pageData)
   * @param bool $allowCancel
   * @param string $callbackService Service that will be called when the user clicks on the OK button
   * @param string $callbackMethod Service method
   * @param array $callbackParams Optional service params
   * @return \qcl_ui_dialog_Wizard
   */
  function __construct(
    $pageData,
    $allowCancel=true,
    $callbackService,
    $callbackMethod,
    $callbackParams=null )
  {
    $this->dispatchDialogMessage( array(
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
