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
qcl_import("bibliograph_schema_BibtexSchema");

/**
 *
 */
class bibliograph_model_export_Csv
  extends bibliograph_model_export_AbstractExporter
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
  protected $name ="Comma-separated values";

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
   * Converts an array of bibliograph record data to a CSV string
   *
   * @param array $data
   *     Reference data
   * @param array|null $exclude
   *     If given, exclude the given fields
   * @return string
   *     Csv string
   */
  function export( $data, $exclude=array() )
  {
    qcl_assert_array( $data, "Invalid data");
    
    // from https://gist.github.com/johanmeiring/2894568
    function str_putcsv($input, $delimiter = ',', $enclosure = '"')
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $input, $delimiter, $enclosure);
        rewind($fp);
        $data = fread($fp, 1048576);
        fclose($fp);
        return rtrim($data, "\n");
    }
    
    $csv = str_putcsv( array_keys( $data[0] ) ) . PHP_EOL;
    
    foreach( $data as $line)
    {
      $csv .= str_putcsv( array_values( $line ) ) . PHP_EOL;
    }
    return $csv;
  }
}
