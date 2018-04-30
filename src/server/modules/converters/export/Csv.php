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

namespace app\modules\converters\export;
use app\schema\BibtexSchema;
use app\models\Reference;

/**
 * Parser for UTF-8 encoded BibTeX files
 */
class Csv extends AbstractExporter
{

  /**
   * @inheritdoc
   */
  public $id = "csv";

  /**
   * @inheritdoc
   */
  public $name = "Comma-separated values (UTF-8)";

  /**
   * @inheritdoc
   */
  public $type = "export";

  /**
   * @inheritdoc
   */
  public $mimeType = "text/csv";

  /**
   * @inheritdoc
   */
  public $extension = "csv";

  /**
   * @inheritdoc
   */
  public $description = "Exports CSV in UTF-8 encoding, using a semicolon as column separator";


  // from https://gist.github.com/johanmeiring/2894568
  protected function putcsv($input, $delimiter = ';', $enclosure = '"')
  {
    $fp = fopen('php://temp', 'r+');
    fputcsv($fp, $input, $delimiter, $enclosure);
    rewind($fp);
    $data = fread($fp, 1048576);
    fclose($fp);
    return rtrim($data, "\n");
  }

  /**
   * @inheritdoc
   */
  public function exportOne( Reference $reference )
  {
    return $this->export([$reference]);
  }

  /**
   * @param Reference[] $references
   * @return string
   */
  public function export(array $references)
  {
    $fields = $references[0]->getSchema()->fields();
    // header
    $csv = $this->putcsv( $fields ) . PHP_EOL;
    // data
    foreach( $references as $reference) {
      $csv .= $this->putcsv( array_values( $reference->getAttributes($fields) ) ) . PHP_EOL;
    }
    return $csv;
  }
}