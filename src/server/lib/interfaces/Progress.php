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

namespace lib\interfaces;

interface Progress
{

  /**
   * Called at the start of the transmission, sets a few global variables inside the iframe.
   */
  public function start();

  /**
   * API function to set the state of the progress par
   * @param integer $value The valeu of the progress, in percent
   * @param string|null $message
   * @param string|null $newLogText
   */
  public function setProgress(int $value, string $message=null, string $newLogText=null);


  /**
   * Must be called on completion of the script
   * @param string|null Optional message that will be shown in an alert dialog
   */
  public function complete(string $message=null);

}