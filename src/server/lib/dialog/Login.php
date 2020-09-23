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
 * Class Login
 * @package lib\dialog
 * Needs some more love
 */
class Login extends Dialog
{

  /**
   * @var string
   */
  protected $text = "";

  /**
   * @param $value
   * @return $this
   */
  public function setText(string $value){$this->message=$value; return $this;}

  /**
   * @inheritDoc
   */
  public function sendToClient(array $properties=[])
  {
    return parent::sendToClient(array_merge($properties,['text']));
  }
}
