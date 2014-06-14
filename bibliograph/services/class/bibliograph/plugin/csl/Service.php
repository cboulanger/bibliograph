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

qcl_import("qcl_data_controller_Controller");
qcl_import("bibliograph_service_Reference");
qcl_import("bibliograph_schema_BibtexSchema");
qcl_import("bibliograph_plugin_csl_CiteProc");


class class_bibliograph_plugin_csl_Service
  extends qcl_data_controller_Controller
{


  /*
  ---------------------------------------------------------------------------
     API
  ---------------------------------------------------------------------------
  */

  public function method_getStyleData()
  {
    $config = $this->getApplication()->getConfigModel();
    $config->createKeyIfNotExists( "csl.style.default", "string", true, "apa" );

    $styleData = array();
    foreach( scandir( CSL_STYLE_DIR ) as $file )
    {
      if( $file[0] == "." or get_file_extension( $file ) != "csl" ) continue;
      $csl = simplexml_load_file( CSL_STYLE_DIR . "/" . $file );
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
   *
   */
  public function method_render( $datasource, $ids, $style )
  {
    qcl_assert_valid_string( $datasource,"Invalid datasource argument." );
    qcl_assert_array( $ids, "Invalid ids argument");
    qcl_assert_valid_string( $style,"Invalid style argument." );

    //$data = $this->render_debug( $datasource, $ids, $style );
    $data = $this->render( $datasource, $ids, $style );
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
  public function method_renderFolder( $datasource, $folderId, $style )
  {
    qcl_assert_valid_string( $datasource,"Invalid datasource argument." );
    qcl_assert_integer( $folderId, "Invalid folderId argument");
    qcl_assert_valid_string( $style,"Invalid style argument." );

    $dsModel  = $this->getDatasourceModel( $datasource );
    $fldModel = $dsModel->getInstanceOfType("folder");
    $fldModel->load( $folderId );
    $refModel = $dsModel->getInstanceOfType("reference");

    /*
     * get ids of linked records and sort them
     */
    $ids = $refModel->linkedModelIds( $fldModel );

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
      return $this->render( $datasource, $ids, $style );
    }
    return "";
  }

  /**
   * Render the citations
   * @param $datasource
   * @param $ids
   * @param $style
   * @return array
   */
  protected function render( $datasource, $ids, $style )
  {
    qcl_assert_valid_string( $datasource, "Invalid datasource argument!");
    qcl_assert_array( $ids, "Invalid 'ids' argument: must be array!" );

    $refController = bibliograph_service_Reference::getInstance();
    $bibtexSchema = bibliograph_schema_BibtexSchema::getInstance();
    $citeproc = new bibliograph_plugin_csl_CiteProc( $style );

    $data = array();
    $counter = 0;

    foreach( $ids as $id )
    {
      $ref = $refController->method_getData( $datasource, "reference", $id );//FIXME
      $csl = $bibtexSchema->toCslRecord( $ref );
      $csl->id = either( $csl->id, "ITEM-" . $counter++ );
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

  protected function render_debug( $datasource, $ids, $style="apsa" )
  {
    qcl_assert_valid_string( $datasource, "Invalid datasource argument!");
    qcl_assert_array( $ids, "Invalid 'ids' argument: must be array!" );

    qcl_import("bibliograph_service_Reference");
    qcl_import("bibliograph_schema_BibtexSchema");
    qcl_import("bibliograph_plugin_csl_CiteProc");

    $refController = bibliograph_service_Reference::getInstance();
    $bibtexSchema = bibliograph_schema_BibtexSchema::getInstance();
    $citeproc = new bibliograph_plugin_csl_CiteProc( $style );

    $data = array();
    $counter = 0;
    foreach( $ids as $id )
    {
      $ref = $refController->method_getData( $datasource, $id );
      $csl = $bibtexSchema->toCslRecord( $ref );
      $csl->id = either( $csl->id, "ITEM-" . $counter++ );
      $data[ $csl->id ] = $csl;
    }

    $result = "<h2>Debugging style '$style', Reference Type '{$csl->type}'</h2>";
    $result .= "<h3>Formatted Result</h3>";
    foreach( $data as $record ) {
      $result .= $citeproc->render($record) . "<br/>";
    }
    $result .= "<h3>Input Data</h3>";
    $result .= "<pre>" . json_format( $data ) ."</pre>";
    $result .= "<h3>CSL Data</h3>";
    $result .= "<pre>" . htmlentities( $citeproc->csl_data ) . "</pre>";

    return array(
      'html' => $result
    );
  }
}
?>