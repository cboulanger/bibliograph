<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

namespace lib\csl;

use Yii;

require_once "citeproc-php/CiteProc.php";

/**
 * Wrapper around citeproc-php, the php implementation of
 * a CSL formatting engine.
 *
 * @author cboulanger
 */
class CiteProc extends \yii\base\BaseObject
{

  /**
   * The id of the citation style
   * @var string
   */
  protected $styleId;

  /**
   * The citation style processor
   * @var citeproc
   */
  protected $citeproc;

  /**
   * Returns the path to the directory containing the style files
   *
   * @return void
   */
  public static function getStyleDir()
  {
    return dirname(__FILE__) . "/styles";
  }
  /**
   * Constructor
   * @param $styleId
   * @return \csl_CiteProc
   */
  public function __construct( $styleId )
  {
    $this->styleId = $styleId;

    // file with csl data
    $csl_file = static :: getStyleDir() . "/$styleId.csl";
    $this->csl_data = file_get_contents( $csl_file );
    
    //$this->debug($csl_file);

    // create new processor
    $locale = Yii::$app->language;
    $this->citeproc = new citeproc($this->csl_data,substr($locale,0,2));
  }


  /**
   * Render a citation
   * @param array $data An array of arrays in the format expected by the
   * citation processor
   * @return string
   */
  public function render( $data )
  {
    //$this->debug($data);
    $formatted = $this->citeproc->render( $data, 'bibliography' );
    // $formatted = str_replace( ". .",".", $formatted );
    // $formatted = str_replace( ", .",".", $formatted );
    // $formatted = str_replace( ", p. )",")", $formatted );
    // $formatted = str_replace( " (p. )","", $formatted );
    // $formatted = str_replace( ", S )",")", $formatted );
    // $formatted = str_replace( " (S )","", $formatted );
    //$formatted = utf8_clean($formatted);
    //$this->debug($formatted);
    return $formatted;
  }
}
