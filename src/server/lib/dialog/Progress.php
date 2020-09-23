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

/**
 * Class Progress
 * @todo support more properties
 * @package lib\dialog
 */
class Progress extends Dialog
{

  /**
   * @var string
   */
  protected $message = "";

  /**
   * @param $value
   * @return $this
   */
  public function setMessage(string $value){$this->message=$value; return $this;}

  /**
   * The type of the dialog widget
   * @var int
   */
  public $progress = 0;

  /**
   * @param int $value;
   * @return $this
   */
  public function setProgress(int $value){$this->progress = $value; return $this; }

  /**
   * @inheritDoc
   */
  public function sendToClient(array $properties=[])
  {
    return parent::sendToClient(array_merge($properties,['message', 'progress']));
  }
}
