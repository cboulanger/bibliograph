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

/**
 *
 */
class bibliograph_service_Export
  extends qcl_data_controller_Controller
{

  /**
   * Exports the given references to a file in temporary system folder.
   * You need to supply either a folder id or an array of reference ids
   * as second and third argument. If both are provided, the folder id takes
   * precedence. The method returns the name of the file. You can then
   * download the file using the qcl_server_Upload with the "bibliograph_export"
   * datasource and the filename.
   *
   * @param string $datasource
   *    Required name of datasource
   * @param int $folderId
   *    The id of the folder to export
   * @param array $ids
   *    The ids of the references to export.
   * @return string
   *    Name of file
   */
  public function method_exportReferencesDialog( $datasource, $folderId, $ids )
  {
    /*
     * check values
     */
    if ( $folderId )
    {
      qcl_assert_integer( $folderId, "Invalid folder id.");
    }
    else
    {
      qcl_assert_array( $ids, "Invalid ids argument" );
    }

    /*
     * return form
     */
    qcl_import("qcl_ui_dialog_Form");
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
    qcl_import( "bibliograph_model_export_RegistryModel" );
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
   * Handles the dialog data from method_exportReferencesDialog()
   * @param $data
   * @param $datasource
   * @param $folderId
   * @param $ids
   * @return string
   */
  public function method_exportReferencesHandleDialogData( $data, $datasource, $folderId, $ids )
  {
    if ( $data===null )
    {
      return "ABORTED";
    }

    $file = $this->exportReferences( $data->format, $datasource, $folderId, $ids );
    $name = explode("_",$file);
    $url  = $this->getServerInstance()->getUrl() .
      "?download=true" .
      "&application=bibliograph" .
      "&sessionId=" . $this->getSessionId() .
      "&datasource=bibliograph_export" .
      "&name=" . $name[1] .
      "&id=$file&delete=true";

    $this->dispatchClientMessage("window.location.replace", array(
      'url' => $url
    ) );
    return "";
  }

  /**
   * Exports the given references to a file in temporary system folder.
   * You need to supply either a folder id or an array of reference ids
   * as second and third argument. If both are provided, the folder id takes
   * precedence. The method returns the name of the file. You can then
   * download the file using the qcl_server_Upload with the "bibliograph_export"
   * datasource and the filename.
   *
   * @param string $format
   *    Required fomat of the export
   * @param string $datasource
   *    Required name of datasource
   * @param int $folderId
   *    The id of the folder to export
   * @param array $ids
   *    The ids of the references to export.
   * @return string
   *    Name of file
   */
  public function exportReferences( $format, $datasource, $folderId, $ids )
  {
    qcl_assert_valid_string( $format );
    qcl_assert_valid_string( $datasource );

    qcl_import("bibliograph_model_export_RegistryModel");
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
    if ( $folderId )
    {
      $fldModel = $dsModel->getInstanceOfType("folder");
      $fldModel->load( $folderId );
      $query = $refModel->findLinked( $fldModel );
    }
    else
    {
      $query = $refModel->getQueryBehavior()->selectIds( $ids );
    }

    /*
     * convert selected records and save to file
     */
    $file->open("w");
    $data = array();
    while( $refModel->loadNext( $query ) )
    {
      $data[] = $refModel->data();
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
?>