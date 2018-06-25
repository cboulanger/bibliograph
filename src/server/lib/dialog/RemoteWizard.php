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

/*
 * A wizard-type widget that constructs the wizard pages on-the-fly, using
 * functionality from qcl.ui.dialog.Form. In contrast to qcl.ui.dialog.Wizard,
 * this wizard sends each page result back to the server and gets new page data
 */
class RemoteWizard extends Dialog
{
  /**
   * Returns an event to the client which prompts the user with a remote wizard widget.
   *
   * @param array $pageData 
   *    Array containing the page data (see qcl.ui.dialog.Wizard#pageData)
   * @param int $page 
   *    The wizard page to open
   * @param bool $allowCancel 
   *    Whether to show a "Cancel" button. (Default: false)
   * @param bool $allowFinish 
   *    Whether to allow the user to skip the remaining pages and finish the wizard (Default: false).
   * @param string $callbackService 
   *    Service that will be called when the user clicks on the OK button
   * @param string $callbackMethod 
   *    Service method
   */
  public static function create(
    $pageData,
    $page,
    $allowCancel = true,
    $allowFinish = false,
    $callbackService,
    $callbackMethod
  ) {
    static::addToEventQueue( [
     'type'       => "remoteWizard",
     'properties' => [
        'serviceName'   => $callbackService,
        'serviceMethod' => $callbackMethod,
        'pageData'      => $pageData,
        'page'          => $page,
        'allowCancel'   => $allowCancel,
        'allowFinish'   => $allowFinish
     ]
    ]);
  }
}
