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

class Prompt extends Alert
{

  /**
   * The default text for the prompt
   * @var string|null
   */
  public $default = null;

  /**
   * Set the default text for the prompt
   * @param string $value
   * @return $this
   */
  public function setDefault($value){$this->default = $value; return $this;}

  /**
   * Set whether input is required
   * @var bool
   */
  public $requireInput = false;

  /**
   * Whether input is required
   * @param bool $value
   * @return $this
   */
  public function setRequireInput($value){$this->default = $value; return $this;}

  /**
   * The timeout in seconds before the prompt is auto-submitted after a value has been entered
   * @var bool
   */
  public $autoSubmitTimeout = false;

  /**
   * Set the timeout in seconds before the prompt is auto-submitted after a value has been entered
   * @param int $value
   * @return $this
   */
  public function setAutoSubmitTimeout($value){$this->default = $value; return $this;}

  /**
   * @inheritDoc
   */
  public function sendToClient(array $properties=[])
  {
    return parent::sendToClient(array_merge($properties,['default','requireInput', 'autoSubmitTimeout']));
  }
}
