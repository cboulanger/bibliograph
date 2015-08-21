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
qcl_import("bibliograph_schema_BibtexSchema");

/**
 *
 */
class bibliograph_model_import_Csv
  extends bibliograph_model_import_AbstractImporter
{

  /**
   * The id of the format
   * @var string
   */
  protected $id = "Csv";

  /**
   * The name of the format
   * @var string
   */
  protected $name = "Comma-separated values (UTF-8)";

  /**
   * The type of the format
   * @var string
   */
  protected $type = "bibliograph";

  /**
   * The file extension of the format
   * @var string
   */
  protected $extension = "csv";

  /**
   * Parses Csv string data into a bibliograph-compatible array.
   *
   * @param string $string
   *    A Csv string
   * @param bibliograph_model_ReferenceModel $targetModel
   *    The model in which to import the data (for information about the model)
   * @return array
   *    The result array which can be imported into bibliograph
   * @throws InvalidArgumentException
   *    if input data cannot be parsed
   */
  function import( $string, bibliograph_model_ReferenceModel $targetModel )
  {
    $header  = NULL;
    $schemaModel = $targetModel->getSchemaModel();
    $fields = $schemaModel->fields();
    $types  = $schemaModel->types();
    $fieldKeys = array_flip($fields);
    $data = array();
    $rows = array_filter(explode(PHP_EOL, $string));  
    foreach($rows as $row)
    {
        $row = str_getcsv ($row, ",", '"' , "\\");
        if( ! $header )
        {
          $header = $row;
          $numCols = count($header);
          $invalidFields = array_diff( $header, $fields );
          if( count( $invalidFields ) == count( $header ) )
          {
            throw new JsonRpcException( $this->tr("Data contains no importable columns.") );
          }          
        }
        else
        {
          if( ! count($row) ) continue;
          $row = array_combine($header, array_slice($row,0,$numCols));
          $row = array_intersect_key( $row, $fieldKeys );
          $row = array_filter( $row );
          
          // reftype
          if( ! isset($row['reftype']) or !$row['reftype'] or ! in_array($row['reftype'], $types) )
          {
            $row['reftype'] = "book";
          }
          $data[] = $row;
        }
    }
    return $data;
  }
}
