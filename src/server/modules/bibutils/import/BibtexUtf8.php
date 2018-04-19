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

namespace app\modules\bibutils\import;

use Yii;
use app\models\Reference;
use lib\bibtex\BibtexParser;

/**
 * Parser for UTF-8 encoded BibTeX files
 */
class BibtexUtf8 extends AbstractParser
{

  /**
   * @inheritdoc
   */
  public $id = "bibtexutf8";

  /**
   * @inheritdoc
   */
  public $name = "BibTex with UTF-8 character encoding";

  /**
   * @inheritdoc
   */
  public $type = "bibutils";

  /**
   * @inheritdoc
   */
  public $extension = "bib,bibtex";


  /**
   * @inheritdoc
   */
  public $description = "This importer expects the BibTeX format in UTF-8. It does not convert LaTeX characters such as \\\"{a}";

  /**
   * @inheritdoc
   */
  public function parse( string $bibtex )
  {
    $parser = new BibtexParser();
    $records = $parser->parse($bibtex);
    if (count($records) === 0) {
      Yii::debug("Data did not contain any parseable records.");
      return [];
    }
    $references = [];
    foreach ($records as $item) {
      $p = $item->getProperties();
      // fix bibtex parser issues and prevemt validation errors


      foreach ( $p as $key => $value ) {

        // some implementations put different authors/editors in separate fields and attach a suffix
        if ( starts_with($key, ["editor","author"]) and strlen($key) > 6 ){
          $key = substr($key,0,6);
          if( isset($p[$key]) ){
            // add more that one author/editor field with a semicolon as separator
            $value = $p[$key] . "; ";
          }
        }
        switch ($key){
          case "author":
          case "editor":
            $p[$key] = str_replace("{", "", $value);
            $p[$key] = str_replace("}", "", $value);
            break;
          case "date":
            // non-standard, but often used
            $year = date( "Y", strtotime($p[$key]));
            if( $year ){
              $p['year'] = $year;
            }
          case "journal":
          case "journaltitle":
            // non-standard
            $key = "journal";
            if(isset($p['journalsubtitle']) ){
              $value = $value . ". " . $p['journalsubtitle'];
              unset($p['journalsubtitle']);
            }
            break;
          case "journalsubtitle":
            // non-standard
            continue;
          case "issue":
            // non-standard
            unset($p[$key]);
            $key = "number";
            break;
          case "booksubtitle":
            // non-standard
            unset($p[$key]);
            $key = "subtitle";
            break;
          case "shortjournal":
            // non-standard
            // use journal abbreviation only if we have no journal title
            unset($p["shortjournal"]);
            if( isset($p['journal'])) continue;
            $key = "journal";
            break;
        }
        // remove "opt" prefix
        if( starts_with($key, "opt") ){
          $key = substr($key, 3);
        }
        try {
          $columnSchema = Reference::getDb()->getTableSchema(Reference::tableName())->getColumn($key);
        } catch (\Exception $e) {
          Yii::warning($e->getMessage());
        }
        if( $columnSchema === null ) {
          Yii::warning("Skipping non-existent column '$key'...");
        } elseif( is_string($value) and $columnSchema->size ){
          $p[$key] = substr( $value, 0, $columnSchema->size );
        }
      }
      $references[] = array_merge($p, [
        'citekey' => $item->getItemID(),
        'reftype' => $item->getItemType()
      ]);
    }
    return $references;
  }
}