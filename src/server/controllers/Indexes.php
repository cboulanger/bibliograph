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
    $schemaModel = static::getControlledModel( $datasource )::getSchema();
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
        'label'   => Yii::t('app', "Index"),
        'type'    => "selectbox",
        'options' => $fieldOptions
      ),
      
      'field'  => array(
        'label'   => Yii::t('app', "Action"),
        'type'    => "selectbox",
        'options' => array(
          array( "label" => Yii::t('app', "Delete entry"), "value" => "delete" ),
          array( "label" => Yii::t('app', "Merge entries"), "value" => "merge" )
        )
      ),      
      
      'backup'  => array(
        'label'   => Yii::t('app', "Create a backup?"),
        'type'    => "selectbox",
        'options' => array(
          array( 'value' => true, 'label' => Yii::t('app', "Yes") ),
          array( 'value' => false, 'label' => Yii::t('app', "No") )
        )
      ),
    );
    
    
    if ( ! qcl_application_plugin_Manager::getInstance()->isActive("backup") )
    {
      unset( $formData['backup'] );
    }
    
    return \lib\dialog\Form::create(
      Yii::t('app', "You can edit indexes here." ) . " " .
      Yii::t('app', "These changes cannot easily be undone, that is why it is recommended to create a backup."),
      $formData,
      true,
      Yii::$app->controller->id, "confirmEdit",
      func_get_args()
    );
  }

  public function method_confirmEdit( $data, $datasource )
  {
    if ( $data === null )
    {
      return "ABORTED";
    }

    $schemaModel = static::getControlledModel( $datasource )::getSchema();

    return \lib\dialog\Form::create(
      $message,
      $formData,
      true,
      Yii::$app->controller->id, "executeEdits",
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
      
      $backupService = new backup_Backup();
      $comment = "Automatically created by " . __CLASS__;
      $zipfile = $backupService->createBackup( $datasource, null, $comment );
    }

    $model = static::getControlledModel($datasource);

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
    return \lib\dialog\Alert::create(
      Yii::t('app',"%s replacements made. %s",
        $count,
        $data->backup
           ? Yii::t('app',"In case you want to revert the changes, a backup file has been created.",$zipfile)
           : ""
      )
    );
  }
}