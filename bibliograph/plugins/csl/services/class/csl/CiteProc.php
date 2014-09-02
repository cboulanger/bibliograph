<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

require_once "citeproc-php/CiteProc.php";
//require_once "qcl/lib/portable-utf8.php";

/**
 * Wrapper around citeproc-php, the php implementation of
 * a CSL formatting engine.
 *
 * @author cboulanger
 */
class csl_CiteProc
  extends qcl_core_Object
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
   * Constructor
   * @param $styleId
   * @return \csl_CiteProc
   */
  public function __construct( $styleId )
  {
    $this->styleId = $styleId;

    /*
     * file with csl data
     */
    $csl_file = dirname(__FILE__) . "/styles/$styleId.csl";
    $this->csl_data = file_get_contents( $csl_file );
    
    //$this->debug($csl_file);

    /*
     * create new processor
     */
    $locale = qcl_locale_Manager::getInstance()->getLocale();
    $this->citeproc = new citeproc($this->csl_data,substr($locale,0,2));
  }


  /**
   * Render a citation
   * @param array $data
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
