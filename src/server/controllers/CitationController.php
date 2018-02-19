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

namespace app\controllers;

use Yii;
use lib\csl\CiteProc;
use app\models\Datasource;

class CitationController extends AppController
{

  /*
  ---------------------------------------------------------------------------
     API
  ---------------------------------------------------------------------------
  */

  /**
   * Action to return model data on the available csl styles
   *
   * @return void
   */
  public function actionStyleData()
  {
    $styleDir  = CiteProc::getStyleDir();
    $styleData = array();
    foreach( scandir( $styleDir ) as $file )
    {
      if( $file[0] == "." or get_file_extension( $file ) != "csl" ) continue;
      $csl = simplexml_load_file( "$styleDir/$file" );
      $styleData[] = array(
        'id'      => substr( $file, 0, -4 ),
        'title'   => (string) $csl->info->title
      );
    }
    return $styleData;
  }

  /**
   * Render the given references in the given formatting style.
   * @param string $datasource
   * @param array $ids
   * @param string $style
   * @return array
   *    Associative array with a key "html", which has a string
   *    value, the rendered html
   */
  public function actionRenderItems( $datasource, $ids, $style )
  {
    //$data = $this->render_debug( $datasource, $ids, $style );
    $data = self :: process( $datasource, $ids, $style );
    //$this->debug($data);
    return $data;
  }

  /**
   * Render the full content of a folder
   * @param $datasource
   * @param $folderId
   * @param $style
   * @return string HTML
   */
  public function actionRenderFolder( $datasource, $folderId, $style )
  {
    not_implemented();
    $folderModel = $this->getModelClass( $datasource, "folder" );
    $referenceModel = $this->getModelClass( $datasource, "reference" );

    // search folder
    if ( $folderModel->searchFolder ) {
      return $this->actionRenderQuery( $datasource, $folderModel->query, $style );
    }
    
    // search references
    $folder = $folderModel::findOne($folderId);

    $max = $this->getApplication()
      ->getConfigModel()
      ->getKey( "plugin.csl.bibliography.maxfolderrecords" );

    if ( count( $ids ) > $max )
    {
      $msg = $this->tr(
        "More than %s references in the folder. Creating a bibliography would take too long.",
        $max
      );
      qcl_import("qcl_ui_dialog_Alert");
      new qcl_ui_dialog_Alert( $msg );
      return array(
        'html' => ""
      );
    }
    elseif ( count( $ids ) )
    {
      $ids = implode( ",", $ids );
      $query = new qcl_data_db_Query( array(
        'where'   => "id IN ($ids)",
        'orderBy' => array("author","year","title")
      ) );
      $ids = $refModel->getQueryBehavior()->fetchValues("id", $query );
      return self :: process( $datasource, $ids, $style );
    }
    return "";
  }
  
  /**
   * process the result of a query
   * @param $datasource
   * @param $folderId
   * @param $style
   * @return string HTML
   */
  public function actionRenderQuery( $datasource, $query, $style )
  {
    not_implemented();
    $app = $this->getApplication();
    $dsModel  = $this->getDatasourceModel( $datasource );
    $refModel = $dsModel->getInstanceOfType("reference");

    try
    {
      qcl_import( "bibliograph_schema_CQL" );
      $cql =  bibliograph_schema_CQL::getInstance();      
      $q = $cql->addQueryConditions( 
        (object) array(  "cql" => $query ), 
        new qcl_data_db_Query( array( 'orderBy' => array("author","year","title") ) ), 
        $refModel 
      );
      $app->getLocaleManager()->setAppNamespace(null);
    }
    catch( bibliograph_schema_Exception $e)
    {
      throw new qcl_server_ServiceException($e->getMessage());
    }
    $q->where['markedDeleted'] = false;
    
    $ids = $refModel->getQueryBehavior()->fetchValues("id", $q );
    
    return self :: process( $datasource, $ids, $style );
  }  

  /**
   * process citations
   * @param $datasource
   * @param $ids
   * @param $style
   * @return array Array containing the key "html" with the rendered result
   */
  public static function process( $datasource, $ids, $style )
  {
    $bibtexSchema = new \lib\schema\BibtexSchema;
    $citeproc = new \lib\csl\CiteProc($style);

    $data = array();
    $counter = 0;

    $references = $this->getModelClass( $datasource, "reference" ) :: findAll( $ids );
    foreach( $references as $reference )
    {
      $csl = $bibtexSchema->toCslRecord( $reference->getAttributes() );
      $csl->id = $csl->id ? $csl->id : "ITEM-" . $counter++;
      $data[ $csl->id ] = $csl;
    }

    $result = "";
    foreach( $data as $record ) {
      $result .= $citeproc->render($record) . "<br/>";
    }
    return array(
      'html' => $result
    );
  }
}
