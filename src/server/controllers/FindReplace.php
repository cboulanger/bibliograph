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
    $schemaModel = $this->getControlledModel( $datasource )::getSchema();
    $fields = $schemaModel->fields();
    $fieldOptions = array();
//    $fieldOptions[] = array( 'value' => "all", 'label' => Yii::t('app', "All fields") ); // not implemented yet

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
        'label'   => Yii::t('app', "Search in:"),
        'type'    => "selectbox",
        'options' => array(
          array( 'value' => 'all',      'label' => Yii::t('app', "Whole database") ),
          array( 'value' => 'selected', 'label' => Yii::t('app', "Selected records") ),
          array( 'value' => 'folder',   'label' => Yii::t('app', "Selected folder") )
        ),
        'width'   => 200,
      ),
      'field'  => array(
        'label'   => Yii::t('app', "Replace in:"),
        'type'    => "selectbox",
        'options' => $fieldOptions
      ),
      'find'  => array(
        'label'   => Yii::t('app', "Search expression:"),
        'type'    => "textfield"
      ),
      'replace'  => array(
        'label'   => Yii::t('app', "Replace with:"),
        'type'    => "textfield"
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
      Yii::t('app', "You can do a 'find and replace' operation on all or selected records in the database. These changes cannot easily be undone, that is why it is recommended to create a backup."),
      $formData,
      true,
      Yii::$app->controller->id, "confirmFindReplace",
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
      'all'       => Yii::t('app', "in all records"),
      'selected'  => Yii::t('app', "in the selected records"),
      'folder'    => Yii::t('app', "in the selected folder")
    );
    $schemaModel = $this->getControlledModel( $datasource )::getSchema();

    $args =  func_get_args();
    return \lib\dialog\Confirm::create(
      Yii::t('app',"Are you sure you want to replace '%s' with '%s' in %s %s?",
       $data->find, $data->replace,
       $data->field == "all"
         ? Yii::t('app', "all fields")
         : Yii::t('app', "field '%s'", $schemaModel->getFieldLabel( $data->field ) ),
       $msg_map[$data->scope]
      ),
      null,
      Yii::$app->controller->id, "findReplace", $args
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