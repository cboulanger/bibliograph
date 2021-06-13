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
 * @package lib\dialog
 */
class Progress extends Dialog
{

  // message
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
   * @var string
   */
  protected $logContent = "";

  /**
   * @param $value
   * @return $this
   */
  public function setLogContent(string $value){$this->logContent=$value; return $this;}

  /**
   * @var string
   */
  protected $newLogText = "";

  /**
   * @param $value
   * @return $this
   */
  public function setNewLogText(string $value){$this->newLogText=$value; return $this;}

  // showProgressBar
  /**
   * @var bool
   */
  protected $showProgressBar = false;

  /**
   * @param boolean $value
   * @return $this
   */
  public function setShowProgressBar(bool $value){$this->showProgressBar=$value; return $this;}

  // showLog
  /**
   * @var boolean
   */
  protected $showLog = false;

  /**
   * @param boolean $value
   * @return $this
   */
  public function setShowLog(bool $value){$this->showLog=$value; return $this;}

  // okButtonText
  /**
   * @var string
   */
  protected $okButtonText = "";

  /**
   * @param string|null $value
   * @return $this
   */
  public function setOkButtonText($value){$this->okButtonText=$value; return $this;}

  // hideWhenCompleted
  /**
   * @var boolean
   */
  protected $hideWhenCompleted = false;

  /**
   * @param boolean $value
   * @return $this
   */
  public function setHideWhenCompleted(bool $value){$this->hideWhenCompleted=$value; return $this;}

  // hideWhenCancelled
  /**
   * @var boolean
   */
  protected $hideWhenCancelled = false;

  /**
   * @param boolean $value
   * @return $this
   */
  public function setHideWhenCancelled(bool $value){$this->hideWhenCancelled=$value; return $this;}

  /**
   * @inheritDoc
   */
  public function sendToClient(array $properties=[])
  {
    return parent::sendToClient(array_merge($properties,[
      'message', 'progress', 'logContent', 'newLogText', 'showProgressBar', 'showLog', 'okButtonText', 'hideWhenCompleted', 'hideWhenCancelled'
    ]));
  }
}
