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

qcl_import( "qcl_data_controller_Controller" );
qcl_import("qcl_ui_dialog_Form");
qcl_import("qcl_ui_dialog_Popup");
qcl_import("bibliograph_model_export_RegistryModel");

/**
 *
 */
class bibliograph_service_Export
  extends qcl_data_controller_Controller
{

  /**
   * Returns a dialog to export references
   * @see bibliograph_service_Export::exportReferences for signature
   * @return qcl_ui_dialog_Form
   */
  public function method_exportReferencesDialog( $datasource, $selector )
  {
    return new qcl_ui_dialog_Form(
      "<b>" . _("Export references") . "</b>",
      array(
        'format'  => array(
          'label'   => _("Choose the export format"),
          'type'    => "selectbox",
          'options' => $this->getFormatListData()
        )
      ),
      true, // allow cancel
      $this->serviceName(),
      "exportReferencesHandleDialogData",
      $this->serviceParams()
    );
  }

  /**
   * Returns qx.ui.form.List compatible data with the registered
   * export formats
   *
   * @return array
   */
  protected function getFormatListData()
  {
    $exportRegistry = bibliograph_model_export_RegistryModel::getInstance();

    /*
     * list export formats
     */
    $exportRegistry->findAllOrderBy("name");
    $listData = array();
    while( $exportRegistry->loadNext() )
    {
      $listData[] = array(
        'label' => $exportRegistry->getName(),
        'value' => $exportRegistry->namedId()
      );
    }
    return $listData;
  }

  /**
   * Handles the dialog data from method_exportReferencesDialog().
   * @see bibliograph_service_Export::exportReferences for signature
   * @return qcl_ui_dialog_Popup
   */
  public function method_exportReferencesHandleDialogData( $data, $datasource, $selector )
  {
    if ( $data===null )
    {
      return "ABORTED";
    }
    return new qcl_ui_dialog_Popup(
      Yii::t('app',"Preparing export data. Please wait..."),
      $this->serviceName(), "exportReferencesStartExport",
      array($this->shelve($data, $datasource, $selector))
    );
  }

  /**
   * Service to create a file with the export data for download by the client.
   * Dispatches a message which will trigger the download
   * @param $dummy
   * @param $shelfId
   * @return string
   */
  public function method_exportReferencesStartExport( $dummy, $shelfId )
  {
    list( $data, $datasource, $selector ) = $this->unshelve( $shelfId );

    $file = $this->exportReferences( $data->format, $datasource, $selector);
    $name = explode("_",$file);
    $url  = $this->getServerInstance()->getUrl() .
      "?download=true" .
      "&application=bibliograph" .
      "&sessionId=" . $this->getSessionId() .
      "&datasource=bibliograph_export" .
      "&name=" . $name[1] .
      "&id=$file&delete=true";

    new qcl_ui_dialog_Popup(""); // hide the popup
    $this->dispatchClientMessage("window.location.replace", array(
      'url' => $url
    ) );
    return "OK";
  }

  /**
   * Exports the given references to a file in temporary system folder.
   * The method returns the name of the file. You can then
   * download the file using the qcl_server_Upload with the "bibliograph_export"
   * datasource and the filename.
   *
   * @param string $format
   *    Required fomat of the export
   * @param string $datasource
   *    Required name of datasource
   * @param int|string|array $selector
   *    If integer, the id of the folder. If string, a query. If an array, the ids
   *    of the references to export
   * @return string
   *    Name of file
   */
  public function exportReferences( $format, $datasource, $selector )
  {
    qcl_assert_valid_string( $format );
    qcl_assert_valid_string( $datasource );

    $exportRegistry = bibliograph_model_export_RegistryModel::getInstance();
    $exporter = $exportRegistry->getExporter( $format );

    $dsModel   = $this->getDatasourceModel( "bibliograph_export" );
    $folderObj = $dsModel->getFolderObject();
    $extension = $exporter->getExtension();
    $filename  = $this->getSessionId() . "_" . $datasource . "." . $extension;
    $file = $folderObj->createOrGetFile( $filename );

    /*
     * source models
     */
    $dsModel  = $this->getDatasourceModel( $datasource );
    $refModel = $dsModel->getInstanceOfType("reference");

    /*
     * select records
     */
    if ( is_integer( $selector ) and $selector > 0 )
    {
      $fldModel = $dsModel->getInstanceOfType("folder");
      $fldModel->load( (int) $selector );
      $query = $refModel->findLinked( $fldModel );
    }
    elseif ( is_array( $selector) )
    {
      $query = $refModel->getQueryBehavior()->selectIds( $selector );
    }
    elseif ( is_string( $selector ) )
    {
      qcl_import( "bibliograph_schema_CQL" );
      try
      {
        $query = bibliograph_schema_CQL::getInstance()->addQueryConditions(
          (object) array( 'cql' => $selector ),
          new qcl_data_db_Query(),
          $refModel
        );
      }
      catch( bibliograph_schema_Exception $e)
      {
        throw new qcl_server_ServiceException($e->getMessage());
      }
      $query->where['markedDeleted'] = false;
      $refModel->getQueryBehavior()->select( $query );
    }
    else
    {
      throw new InvalidArgumentException("Invalid parameters");
    }

    /*
     * convert selected records and save to file
     */
    $file->open("w");
    $data = array();
    $fieldsAsKeys = array_flip( $refModel->getSchemaModel()->fields() );
    while( $refModel->loadNext( $query ) )
    {
      $data[] = array_intersect_key( $refModel->data(), $fieldsAsKeys );
    }
    $result = $exporter->export( $data );
    $file->write( $result );
    $file->close();

    /*
     * return the name of the file
     */
    return $filename;
  }
}
