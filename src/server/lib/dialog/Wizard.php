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
   * @var array
   */
  public $pageData = [];

  /**
   * @param array $value
   * @return $this
   */
  public function setPageData(array $value){$this->pageData=$value; return $this;}

  /**
   * Whether cancelling of the dialog is allowed
   * @var bool
   */
  public $allowCancel = false;

  /**
   * @param $value
   * @return $this
   */
  public function setAllowCancel(bool $value){$this->allowCancel=$value; return $this;}

  /**
   * @inheritdoc
   */
  public function sendToClient($properties=[])
  {
    return parent::sendToClient(array_merge($properties,['pageData','allowCancel']));
  }
}
