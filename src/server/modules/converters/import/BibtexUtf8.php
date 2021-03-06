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

namespace app\modules\converters\import;

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
  public $type = "bibliograph";

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
   * The parser (inofficially) supports parts of the BibLaTeX schema.
   */
  public function parse( string $data ) : array
  {
    $parser = new BibtexParser();
    $records = $parser->parse($data);
    if (count($records) === 0) {
      Yii::debug("Data did not contain any parseable records.", __METHOD__);
      return [];
    }
    $references = [];
    foreach ($records as $item) {
      $p = $item->getProperties();
      // fix bibtex parser issues and prevemt validation errors
      foreach ( $p as $key => $value ) {
        $value = stripslashes($value);
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
            $value = str_replace('{', '',
              str_replace('}', '',
                str_replace(' and ', '; ',
                  str_replace(  PHP_EOL .'and ', '; ',$value)))); // TODO use RegExpr
            break;
          case "chapter":
            unset($p[$key]);
            $key = 'title';
            $p[$key]=$value;
            break;
          case "series":
            if ($item->getItemType()=="inbook"){
              unset($p[$key]);
              $key = 'booktitle';
              $p[$key]=$value;
            }
            break;
          case "date":
            // BibLaTeX
            $year = date( "Y", strtotime($p[$key]));
            if( $year ){
              $p['year'] = $year;
            }
            break;
          case "journal":
          case "journaltitle":
            // BibLaTeX
            $key = "journal";
            if(isset($p['journalsubtitle']) ){
              $value = $value . ". " . $p['journalsubtitle'];
              unset($p['journalsubtitle']);
            }
            break;
          case "journalsubtitle":
            // BibLaTeX
            continue 2;
          case "issue":
            // BibLaTeX
            unset($p[$key]);
            $key = "number";
            $p[$key]=$value;
            break;
          case "booksubtitle":
            // BibLaTeX
            unset($p[$key]);
            $key = "subtitle";
            $p[$key]=$value;
            break;
          case "shortjournal":
            // BibLaTeX
            // use journal abbreviation only if we have no journal title
            unset($p["shortjournal"]);
            if( isset($p['journal'])) continue 2;
            $key = "journal";
            $p[$key]=$value;
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
          unset($p[$key]);
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
