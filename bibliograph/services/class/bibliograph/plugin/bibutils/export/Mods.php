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

qcl_import("bibliograph_model_export_AbstractExporter");
qcl_import("bibliograph_model_export_Bibtex");
qcl_import("qcl_util_system_Executable");

/**
 *
 */
class bibliograph_plugin_bibutils_export_Mods
  extends bibliograph_model_export_AbstractExporter
{
  /**
   * The id of the format
   * @var string
   */
  protected $id = "mods";

  /**
   * The name of the format
   * @var string
   */
  protected $name ="MODS";

  /**
   * The type of the format
   * @var string
   */
  protected $type = "bibutils";

  /**
   * The file extension of the format
   * @var string
   */
  protected $extension = "xml";

  /**
   * The object that converts the native array format into
   * bibtex
   *
   * @var bibliograph_model_export_Bibtex
   */
  protected $bibtexExporter;

  /**
   * The binary that does the conversion from bibtex to the target format
   * @var qcl_util_system_Executable
   */
  protected $exporter;

  /**
   * Construcotr
   */
  public function __construct()
  {
    $this->bibtexExporter = bibliograph_model_export_Bibtex::getInstance();
    $this->exporter = new qcl_util_system_Executable( BIBUTILS_PATH . "bib2xml");
  }

  /**
   * Converts an array of bibliograph record data to a MODS XML record
   *
   * @param array $data
   *     Reference data
   * @param array|null $exclude
   *     If given, exclude the given fields
   * @return string
   *     XML document
   */
  function export( $data, $exclude=array() )
  {
    qcl_assert_array( $data, "Invalid data");
    $bibtex = $this->bibtexExporter->export( $data, $exclude );
    $export = $this->exporter->call("-nl -i unicode", $bibtex );
    return $export;
  }
}
?>