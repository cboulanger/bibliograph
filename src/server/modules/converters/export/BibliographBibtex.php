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
class BibliographBibtex extends AbstractExporter
{

  /**
   * @inheritdoc
   */
  public $id = "bibliographBibtex";

  /**
   * @inheritdoc
   */
  public $name = "Bibliograph BibTex (UTF-8)";

  /**
   * @inheritdoc
   */
  public $type = "export";

  /**
   * @inheritdoc
   */
  public $mimeType = "text/x-bibtex";

  /**
   * @inheritdoc
   */
  public $extension = "bib";

  /**
   * @inheritdoc
   */
  public $description = "Exports BibTeX UTF-8 including non-standard types and fields used by Bibliograph";

  /**
   * @inheritdoc
   */
  public function exportOne( Reference $reference )
  {
    $indent = "  ";
    $bibtex = '@' . $reference->reftype . '{' . $reference->citekey;
    $fields = (new BibtexSchema())->fields();
    $attributes = $reference->getAttributes($fields,['reftype','citekey']);
    foreach( $attributes as $field => $value ){
      if( !$value ) continue;
      switch( $field ){
        case "author":
        case "editor":
          $value = implode( PHP_EOL . $indent . $indent . "and ", array_map( function($creator) {
            return trim($creator);
          }, array_filter( explode( ";", $value ), function($creator){
            return !!trim($creator);
          } ) ) );
      }
      $bibtex .= ',' . PHP_EOL . $indent . $field . ' = "' . addslashes($value) . '"';
    }
    $bibtex .=  PHP_EOL .  '}' . PHP_EOL . PHP_EOL;
    return $bibtex;
  }

  /**
   * @param array $references
   * @return string
   */
  public function export(array $references)
  {
    $bibtex = "";
    foreach ( $references as $reference ){
      $bibtex .= $this->exportOne($reference);
    }
    return $bibtex;
  }
}