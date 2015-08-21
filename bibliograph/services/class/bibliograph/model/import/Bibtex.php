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

/**
 *
 */
class bibliograph_model_import_Bibtex
  extends bibliograph_model_import_AbstractImporter
{

  /**
   * The id of the format
   * @var string
   */
  protected $id = "bibtex";

  /**
   * The name of the format
   * @var string
   */
  protected $name = "BibTeX";

  /**
   * The type of the format
   * @var string
   */
  protected $type = "bibliograph";

  /**
   * The file extension of the format
   * @var string
   */
  protected $extension = "bib";

  /**
   * Parses bibtex string data into a bibliograph-compatible array.
   *
   * @param string $bibtex
   *    A bibtex string
   * @param bibliograph_model_ReferenceModel $targetModel
   *    The model in which to import the data (for information about the model)
   * @return array
   *    The result array which can be imported into bibliograph
   * @throws InvalidArgumentException
   *    if input data cannot be parsed
   */
  function import( $bibtex, bibliograph_model_ReferenceModel $targetModel )
  {
    require_once "bibliograph/lib/bibtex/BibtexParser.php";
    $parser  = new BibtexParser;
    $records = $parser->parse( $bibtex );

    if ( count( $records) === 0 )
    {
      throw new InvalidArgumentException("Invalid bibtex data");
    }

    $result = array();
    foreach( $records as $item )
    {
      $p = $item->getProperties();

      /*
       * fix bibtex issues
       */
      foreach( array("author","editor") as $key )
      {
        $p[$key] = str_replace( "{", "", $p[$key]);
        $p[$key] = str_replace( "}", "", $p[$key]);
      }

      $p['citekey'] = $item->getItemID();
      $p['reftype'] = $item->getItemType();

      $result[] = $p;
    }
    return $result;  //
  }
}
