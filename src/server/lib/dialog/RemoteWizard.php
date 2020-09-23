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
class RemoteWizard extends Wizard
{

  /**
   * Whether cancelling of the dialog is allowed
   * @var int
   */
  public $page = 0;

  /**
   * @param $value
   * @return $this
   */
  public function setPage(int $value){$this->page=$value; return $this;}

  /**
   * Whether finishing the wizard prematurely is allowed
   * @var bool
   */
  public $allowFinish = false;

  /**
   * @param $value
   * @return $this
   */
  public function setAllowFinish(bool $value){$this->allowFinish=$value; return $this;}

  /**
   * @inheritdoc
   */
  public function sendToClient($properties=[])
  {
    return parent::sendToClient(array_merge($properties,['page','allowFinish']));
  }
}
