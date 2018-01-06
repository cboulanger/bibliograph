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
class bibliograph_service_reference_Indexes
  extends bibliograph_service_Reference
{
  
  /**
   * Edit Indexes - Dialog 
   */
  public function method_editDialog( $datasource )
  {
    qcl_assert_valid_string( $datasource, "Invalid datasource argument");
    $this->requirePermission("reference.batchedit");
    
    /*
     * prepare field list
     */
    $schemaModel = $this->getControlledModel( $datasource )->getSchemaModel();
    $fields = $schemaModel->fields();
    $fieldOptions = array();

    foreach( $fields as $field )
    {
      $fieldData = $schemaModel->getFieldData( $field );
      if( isset($fieldData['indexEntry']) and $fieldData['indexEntry'] === true )
      {
        $fieldOptions[] = array( 
          'value' => $field, 
          'label' => $schemaModel->getFieldLabel($field) 
        );
      }
    }

    // sort fields by label
    usort( $fieldOptions, function($a,$b){
      return strcmp($a['label'], $b['label']);
    });

    /*
     * form data
     */
    $formData = array(
      
      'field'  => array(
        'label'   => _("Index"),
        'type'    => "selectbox",
        'options' => $fieldOptions
      ),
      
      'field'  => array(
        'label'   => _("Action"),
        'type'    => "selectbox",
        'options' => array(
          array( "label" => _("Delete entry"), "value" => "delete" ),
          array( "label" => _("Merge entries"), "value" => "merge" )
        )
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
      _("You can edit indexes here." ) . " " .
      _("These changes cannot easily be undone, that is why it is recommended to create a backup."),
      $formData,
      true,
      $this->serviceName(), "confirmEdit",
      func_get_args()
    );
  }

  public function method_confirmEdit( $data, $datasource )
  {
    if ( $data === null )
    {
      return "ABORTED";
    }

    $schemaModel = $this->getControlledModel( $datasource )->getSchemaModel();

    return new qcl_ui_dialog_Form(
      $message,
      $formData,
      true,
      $this->serviceName(), "executeEdits",
      func_get_args()
    );
  }

  public function method_executeEdits( $data, $datasource )
  {
    
    if ( $data === null )
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
      $comment = "Automatically created by " . __CLASS__;
      $zipfile = $backupService->createBackup( $datasource, null, $comment );
    }

    $model = $this->getControlledModel($datasource);

    // action!
   
   $count = 0;
   
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