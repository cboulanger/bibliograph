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

class Select extends Alert
{
  /**
   * @var bool
   */
  protected $allowCancel = true;

  /**
   * @param bool $value
   * @return $this
   */
  public function setAllowCancel(bool $value){$this->allowCancel = $value; return $this;}

  /**
   * Optional properties of the form widget
   * @var array
   */
  public $options = [];

  /**
   * @param $value
   * @return $this
   */
  public function setOptions(array $value){$this->options=$value;return $this;}

  /**
   * @inheritDoc
   */
  public function sendToClient(array $properties=[])
  {
    return parent::sendToClient(array_merge($properties,['allowCancel','options']));
  }
}
