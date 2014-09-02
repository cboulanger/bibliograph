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

qcl_import("bibutils_import_Endnote");

/**
 *
 */
class bibutils_import_Ris
  extends bibutils_import_Endnote
{

  /**
   * The id of the importer
   * @var string
   */
  protected $id = "ris";

  /**
   * The descriptive name of the importer
   * @var string
   */
  protected $name = "RIS tagged format";

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
   * Constructor
   */
  public function __construct()
  {
    parent::__construct();
    $this->importer = new qcl_util_system_Executable( BIBUTILS_PATH . "ris2xml");
  }
}
