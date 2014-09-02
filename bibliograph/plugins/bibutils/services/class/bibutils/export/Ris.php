<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import("bibutils_export_Endnote");

/**
 *
 */
class bibutils_export_Ris
  extends bibutils_export_Endnote
{
  /**
   * The id of the format
   * @var string
   */
  protected $id = "ris";

  /**
   * The name of the format
   * @var string
   */
  protected $name ="RIS tagged format";

  /**
   * The type of the format
   * @var string
   */
  protected $type = "bibutils";

  /**
   * The file extension of the format
   * @var string
   */
  protected $extension = "ris";

  /**
   * Construcotr
   */
  public function __construct()
  {
    parent::__construct();
    $this->exporter = new qcl_util_system_Executable( BIBUTILS_PATH . "xml2ris");
  }
}
