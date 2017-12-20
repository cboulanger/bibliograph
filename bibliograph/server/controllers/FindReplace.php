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

qcl_import("qcl_ui_dialog_Alert");
qcl_import("qcl_ui_dialog_Confirm");
qcl_import("qcl_ui_dialog_Form");
qcl_import("bibliograph_service_Reference");

/**
 * Controller that supplies data for the references
 */
class bibliograph_service_reference_FindReplace
  extends bibliograph_service_Reference
{
  
  /**
   * Find and replace text in record fields - Dialog 
   */
  public function method_findReplaceDialog( $datasource, $folderId, $selectedIds )
  {
    qcl_assert_valid_string( $datasource, "Invalid datasource argument");
    $this->requirePermission("reference.batchedit");
    
    /*
     * prepare field list
     */
    $schemaModel = $this->getControlledModel( $datasource )->getSchemaModel();
    $fields = $schemaModel->fields();
    $fieldOptions = array();
//    $fieldOptions[] = array( 'value' => "all", 'label' => _("All fields") ); // not implemented yet

    foreach( $fields as $field )
    {
      $fieldOptions[] = array( 'value' => $field, 'label' => $schemaModel->getFieldLabel($field) );
    }

    // sort fields by label
    usort( $fieldOptions, function($a,$b){
      return strcmp($a['label'], $b['label']);
    });

    /*
     * form data
     */
    $formData = array(
      'scope'  => array(
        'label'   => _("Search in:"),
        'type'    => "selectbox",
        'options' => array(
          array( 'value' => 'all',      'label' => _("Whole database") ),
          array( 'value' => 'selected', 'label' => _("Selected records") ),
          array( 'value' => 'folder',   'label' => _("Selected folder") )
        ),
        'width'   => 200,
      ),
      'field'  => array(
        'label'   => _("Replace in:"),
        'type'    => "selectbox",
        'options' => $fieldOptions
      ),
      'find'  => array(
        'label'   => _("Search expression:"),
        'type'    => "textfield"
      ),
      'replace'  => array(
        'label'   => _("Replace with:"),
        'type'    => "textfield"
      ),
      'backup'  => array(
        'label'   => _("Create a backup?"),
        'type'    => "selectbox",
        'options' => array(
          array( 'value' => true, 'label' => _("Yes") ),
          array( 'value' => false, 'label' => _("No") )
        )
      ),
    );
    
    qcl_import("qcl_application_plugin_Manager");
    if ( ! qcl_application_plugin_Manager::getInstance()->isActive("backup") )
    {
      unset( $formData['backup'] );
    }
    
    return new qcl_ui_dialog_Form(
      _("You can do a 'find and replace' operation on all or selected records in the database. These changes cannot easily be undone, that is why it is recommended to create a backup."),
      $formData,
      true,
      $this->serviceName(), "confirmFindReplace",
      func_get_args()
    );
  }

  public function method_confirmFindReplace( $data, $datasource, $folderId, $selectedIds )
  {
    if ( $data === null )
    {
      return "ABORTED";
    }

    qcl_assert_valid_string($data->find, "Invalid 'find' argument");
    if ( $data->replace === null ) $data->replace ="";
    qcl_assert_string($data->replace, "Invalid 'replace' argument");

    $msg_map = array(
      'all'       => _("in all records"),
      'selected'  => _("in the selected records"),
      'folder'    => _("in the selected folder")
    );
    $schemaModel = $this->getControlledModel( $datasource )->getSchemaModel();

    $args =  func_get_args();
    return new qcl_ui_dialog_Confirm(
      $this->tr("Are you sure you want to replace '%s' with '%s' in %s %s?",
       $data->find, $data->replace,
       $data->field == "all"
         ? _("all fields")
         : $this->tr( "field '%s'", $schemaModel->getFieldLabel( $data->field ) ),
       $msg_map[$data->scope]
      ),
      null,
      $this->serviceName(), "findReplace", $args
    );
  }

  public function method_findReplace( $go, $data, $datasource, $folderId, $selectedIds )
  {
    if ( ! $go )
    {
      return "ABORTED";
    }

    /*
     * backup?
     */
    if ( $data->backup )
    {
      qcl_import("backup_Backup");
      $backupService = new backup_Backup();
      $comment = "Automatically created by find/replace";
      $zipfile = $backupService->createBackup( $datasource, null, $comment );
    }

    $model = $this->getControlledModel($datasource);

    /*
     * action!
     */
    switch( $data->scope )
    {
      case "all":

        switch( $data->field )
        {
          case "all":
            throw new JsonRpcException("Find/Replace in all columns not yet implemented");

          default:
            //todo: check field
            $table = $model->getQueryBehavior()->getTable();
            $count = $table->replace( $data->field, $data->find, $data->replace );
            break;
        }

        break;

      case "selected":
        qcl_assert_array( $selectedIds );
        throw new JsonRpcException("Find/Replace in selected records not yet implemented");
        break;

      case "folder":
        qcl_assert_integer( $folderId );
        throw new JsonRpcException("Find/Replace in folder not yet implemented");
        break;

      default:
        throw new JsonRpcException("Invalid scope argument");
    }

    /*
     * reload listview
     */
    $this->broadcastClientMessage("mainListView.reload",array(
      "datasource" => $datasource
    ) );

    /*
     * show alert
     */
    return new qcl_ui_dialog_Alert(
      $this->tr("%s replacements made. %s",
        $count,
        $data->backup
           ? $this->tr("In case you want to revert the changes, a backup file has been created.",$zipfile)
           : ""
      )
    );
  }
}