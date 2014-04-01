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

qcl_import("bibliograph_model_import_AbstractImporter");
qcl_import("bibliograph_model_import_Bibtex");
qcl_import("qcl_util_system_Executable");

/**
 *
 */
class bibliograph_plugin_bibutils_import_Mods
  extends bibliograph_model_import_AbstractImporter
{

  /**
   * The id of the importer
   * @var string
   */
  protected $id = "mods";

  /**
   * The descriptive name of the importer
   * @var string
   */
  protected $name = "MODS";

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
   * The binary that does the conversion from the source
   * format to bibtex
   * @var qcl_util_system_Executable
   */
  protected $importer;

  /**
   * The import object which parses bibtex into a native data
   * array
   *
   * @var bibliograph_model_import_Bibtex
   */
  protected $bibtexImporter;

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->importer = new qcl_util_system_Executable( BIBUTILS_PATH . "xml2bib");
    $this->bibtexImporter = bibliograph_model_import_Bibtex::getInstance();
  }

  /**
   * Parses string data into a bibliograph-compatible array.
   *
   * @param string $input
   *    The import data
   * @return array
   *    The result array which can be imported into bibliograph
   * @throws InvalidArgumentException
   *    if input data cannot be parsed
   */
  function import( $input )
  {
    qcl_assert_valid_string( $input );
    $bibtex = $this->importer->call("-nl -fc -o unicode", $input );
    $import = $this->bibtexImporter->import( $bibtex );
    return $import;
  }
}
?>