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

use app\models\User;
use app\models\Role;
use app\models\Group;
use app\models\Datasource;
use \lib\exceptions\UserErrorException;

/**
 * Backend service class for the access control tool widget
 */
class AccessConfigController extends \app\Controllers\AppController
{

  /*
  --------------------------------------------------------------------
    Editing access object data
  --------------------------------------------------------------------
   */

  /**
   * Returns a map of data on the models that are used for the various xxxElement
   * methods
   * @return array
   */
  public function getModelData()
  {
    return  array(
      'user'        => array(
        'class'       => \app\models\User::class,
        'label'       => Yii::t('app',"Users"),
        'dialogLabel' => Yii::t('app',"User"),
        'labelProp'   => "name",
        'icon'        => "icon/16/apps/preferences-users.png"
      ),
      'role'        => array(
        'class'       => \app\models\Role::class,
        'label'       => Yii::t('app',"Roles"),
        'dialogLabel' => Yii::t('app',"Role"),
        'labelProp'   => "name",
        'icon'        => "icon/16/apps/internet-feed-reader.png"
      ),
      'group'        => array(
        'class'       => \app\models\Group::class,
        'label'       => Yii::t('app',"Groups"),
        'dialogLabel' => Yii::t('app',"Group"),
        'labelProp'   => "name",
        'icon'        => "icon/16/actions/address-book-new.png"
      ),
      'permission'  => array(
        'class'       => \app\models\Permission::class,
        'label'       => Yii::t('app',"Permissions"),
        'dialogLabel' => Yii::t('app',"Permission"),
        'labelProp'   => "namedId",
        'icon'        => "icon/16/apps/preferences-security.png"
      ),
      'datasource'  => array(
        'class'       => \app\models\Datasource::class,
        'label'       => Yii::t('app',"Datasources"),
        'dialogLabel' => Yii::t('app',"Datasource"),
        'labelProp'   => "title",
        'icon'        => "icon/16/apps/internet-transfer.png"
      )
    );
  }

  /**
   * Return data from the model map pertaining to the model type
   *
   * @param string $ype
   * @return array
   * @throws \InvalidArgumentsException
   */
  protected function getModelDataFor( $type ){
    if ( ! isset( $this->modelData[ $type ] ) ) {
      throw new \InvalidArgumentsException("Invalid type '$type'");
    }
    return $this->modelData[$type];
  }

  /**
   * Retuns ListItem data for the types of access models
   * @jsonrpc access-config/types
   */
  public function actionTypes()
  {
    $modelData = $this->modelData;
    return array(
      array(
        'icon'    => $modelData['user']['icon'],
        'label'   => Yii::t('app',"Users"),
        'value'   => "user"
      ),
      array(
        'icon'    => $modelData['role']['icon'],
        'label'   => Yii::t('app',"Roles"),
        'value'   => "role"
      ),
      array(
        'icon'    => $modelData['group']['icon'],
        'label'   => Yii::t('app',"Groups"),
        'value'   => "group"
      ),
      array(
        'icon'    => $modelData['permission']['icon'],
        'label'   => Yii::t('app',"Permissions"),
        'value'   => "permission"
      ),
      array(
        'icon'    => $modelData['datasource']['icon'],
        'label'   => Yii::t('app',"Datasources"),
        'value'   => "datasource"
      ),
    );
  }

  /**
   * Action acl-tool/elements
   * Return ListItem data for access models
   *
   * @param string $type
   *    The type of the element
   * @param array|null $filter 
   *    An associative array that can be used in a ActiveQuery::where() method call
   * @throws \lib\exceptions\UserErrorException
   */
  public function actionElements( $type, array $filter=null )
  {
    $this->requirePermission("access.manage");
    $activeUser = $this->getActiveUser();
    $isAdmin = $activeUser->hasRole( "admin" );
    // query
    $elementData = $this->getModelDataFor($type);
    $modelClass = $elementData['class'];
    $labelProp = "name";
    switch ( $type )
    {
      case "user":
        $query = $modelClass::find()->where( [ 'anonymous' => false ] );
        break;
      case "role":
        $query = $modelClass::find();
        break;
      case "group":
        $query = $modelClass::find();
        break;
      case "permission":
        $labelProp = "namedId";
        $query = $modelClass::find();
        break;
      case "datasource":
        $labelProp = "title";
        $query = $modelClass::find();
        break;
      default:
        throw new UserErrorException("Invalid type $type");
    }
    if( $filter ){
      try {
        $query = $query->where( $filter );
      } catch( \Exception $e ) {
        throw new UserErrorException("Invalid filter");
      }
    }
    $records = $query->all();
    $elementData = $this->getModelDataFor($type);
    // create result from record data
    $result = [];
    //Yii::trace($elementData);
    foreach( $records as $record ){
      $value  = $record->namedId;
      $label  = $record->$labelProp;
      $icon   = $elementData['icon'];
      // special cases
      if ( $record->hasAttribute("hidden") and $record->hidden and ! $isAdmin ) continue;
      if ( ! trim($label ) ) $label = $value;
      if ( $record->hasAttribute("ldap") and $record->ldap ) $label .= " (LDAP)";
      if ( $type == "permission" ) {
        $description = $model->description;
        if ( $description ) {
          $label .= sprintf( " (%s)", $description );
        }
      }
      // entry
      $result[] = array(
        'icon'      => $icon,
        'label'     => $label,
        'params'    => $type . "," . $value,
        'type'      => $type,
        'value'     => $value
      );
    }
    return $result;
  }

  /**
   * Returns the tree of model relationships based on the selected element
   * @param $elementType
   * @param $namedId
   * @throws \lib\exceptions\UserErrorException
   */
  public function actionTree( $elementType, $namedId )
  {
    $this->requirePermission("access.manage");
    $modelData = $this->modelData;

    // the edited model element
    $elementData = $this->getModelDataFor($elementType);
    $modelClass = $elementData['class'];
    $model = $modelClass::findByNamedId($namedId);
    if( ! $model ){
      throw new UserErrorException("Model of '$type' with id '$namedId' does not exist.");
    }

    // root node
    $tree = [
      'icon'      => "icon/16/apps/utilities-network-manager.png",
      'label'     => Yii::t('app',"Relations"),
      'value'     => null,
      'type'      => null,
      'children'  => [],      
    ];

    // iterate through the rec and display relations as tree structure
    $types = array_keys($this->modelData);
    foreach( $types as $linkedType ) {
      
      // skip if same
      if( $linkedType == $elementType ) continue;
      // skip role -> user
      if( $elementType == "role" and $linkedType == "users" ) continue;
      // skip if no relation
      try {
        $model->getRelation( $linkedType . "s" ); // this throws if no relation exists
      } catch( \yii\base\InvalidParamException $e ) {
        continue;
      }  

      // normal node
      $linkedElementdata = $this->getModelDataFor($linkedType);
      $node = array(
        'icon'      => $linkedElementdata['icon'],
        'label'     => $linkedElementdata['label'],
        'value'     => $elementType . "=" . $namedId,
        'type'      => $linkedType,
        'mode'      => "link",
        'children'  => []
      );
      
      // user -> roles
      if( $elementType == "user" and $linkedType == "role" ) {
        $user = $model; 
        // you cannot link to this node
        $node['mode'] = null;
        $node['value'] = null;

        // pseudo group node -> no group dependency
        $groupNode = [
          'icon'      => $modelData['group']['icon'],
          'label'     => Yii::t('app',"In all groups"),
          'type'      => "group",
          'value'     => "user=" . $user->namedId,
          'mode'      => "link",
          'children'  => []
        ];
        $roles = $user->getGroupRoles(null)->all();
        foreach( $roles as $role ){
          $roleNode = [
            'icon'      => $modelData['role']['icon'],
            'label'     => $role->name,
            'type'      => "role",
            'mode'      => "unlink",
            'value'     => "role=" . $role->namedId,
            'children'  => []
          ];
          $groupNode['children'][] = $roleNode;
        }
        $node['children'][] = $groupNode;

        // one node for each existing group
        $allGroups = Group::find()->where(['not', ['active' => null]])->all(); // @todo where active=1
        foreach( $allGroups as $group ){
          $groupNode = array(
            'icon'      => $modelData['group']['icon'],
            'label'     => Yii::t('app',"in") . " " . $group->name,
            'type'      => "group",
            'mode'      => "link",
            'value'     => "group=" . $group->namedId . ",user=" . $user->namedId,
            'children'  => []
          );
          $roles = $user->getGroupRoles(null)->all();
          foreach( $roles as $role ){
            $roleNode = array(
              'icon'      => $modelData['role']['icon'],
              'label'     => $role->name,
              'type'      => "role",
              'mode'      => "unlink",
              'value'     => "group=" . $group->namedId . ",role=" . $role->namedId,
              'children'  => []
            );
            $groupNode['children'][] = $roleNode;          
          }
          $node['children'][] = $groupNode;
        }
      } else {
        // other combinations
        $relation = $linkedType . "s";
        foreach( $model->$relation as $linkedModel )
        {
          $linkedNode = [
            'icon'      => $modelData[$linkedType]['icon'],
            'label'     => $linkedModel->getAttribute($modelData[$linkedType]['label']),
            'type'      => $linkedType,
            'mode'      => "unlink",
            'value'     => "$linkedType=" . $linkModel->namedId,
            'children'  => []
          ];
          $node['children'][] = $linkedNode;  
        }
      }
      $tree['children'][] = $node;
    }
    return $tree;
  }

  /**
   * Add a model record
   *
   * @param $type
   * @param $namedId
   * @return string "OK"
   */
  public function actionAdd( $type, $namedId )
  {
    $this->requirePermission("access.manage");
    $modelData = $this->modelData;
    if ( $type == "datasource" )
    {
      
      $mgr = qcl_data_datasource_Manager::getInstance();
      $this->getApplication()->createDatasource( $namedId );
      $model = $mgr->getDatasourceModelByName( $namedId );
      $model->set("title", $namedId );
      $model->save();
      $this->dispatchClientMessage("reloadDatasources");
    }
    else
    {
      $model = $this->getElementModel( $type );
      $model->create($namedId,array(
        $modelData[$type]['labelProp'] => $namedId
      ));
    }
    return $this->method_editElement($type,$namedId);
  }

  /**
   * Delete a model record
   * @param $type
   * @param $ids
   * @return \qcl_ui_dialog_Confirm|string "OK"
   */
  public function actionDelete( $type, $ids )
  {
    $this->requirePermission("access.manage");
    $minId = null;
    switch( $type )
    {
      case "datasource":
        return \lib\dialog\Confirm::create(
          Yii::t('app',"Do you want to remove only the datasource entry or all associated data?"),
          array( Yii::t('app',"All data"), Yii::t('app',"Entry only"), true),
          Yii::$app->controller->id, "deleteDatasource", array($ids)
        );

      case "user":
        $minId = 2; // todo should not be hardcoded
        break;

      case "permission":
        $minId = 29; // todo should not be hardcoded
        break;

      case "role":
        $minId = 5; // todo should not be hardcoded
        break;
    }

    foreach ( (array) $ids as $namedId )
    {
      $model = $this->getElementModel( $type );
      $model->load( $namedId );
      if( $minId and $model->id() < $minId )
      {
        throw new \Exception( Yii::t('app',"Deleting element '%s' of type '%s' is not allowed.", $namedId, $type));
      }
      $model->delete();
    }

    return "OK";
  }

  /**
   * Delete a datasource
   *
   * @param $doDeleteModelData
   * @param $namedId
   * @return 
   */
  public function actionDeleteDatasource( $doDeleteModelData, $namedId )
  {
    if ( $doDeleteModelData === null )
    {
      return "ABORTED";
    }

    $this->requirePermission("access.manage");
    

    try
    {
      qcl_assert_boolean( $doDeleteModelData );
      
      qcl_data_datasource_Manager::getInstance()->deleteDatasource( $namedId, $doDeleteModelData );
      $this->broadcastClientMessage("accessControlTool.reloadLeftList");
    }
    catch ( PDOException $e )
    {
      Yii::warning(  $e->getMessage() );
      return new  qcl_ui_dialog_Alert(Yii::t('app',"Deleting datasource '%s' failed... ",$namedId));
    }

    return new  qcl_ui_dialog_Alert(Yii::t('app',"Datasource '%s' successfully deleted ... ",$namedId));
  }

  protected function getLinkModels( $treeElement, $type, $namedId )
  {

    $elementParts = explode( ",", $treeElement );

    if ( count( $elementParts ) > 1 )
    {
      $depModelInfo = explode( "=", $elementParts[0] );
      $depModel = $this->getElementModel( $depModelInfo[0] );
      qcl_assert_valid_string( $depModelInfo[1] );
      $depModel->load( $depModelInfo[1] );
      $modelInfo = explode( "=", $elementParts[1] );
    }
    else
    {
      $depModel = null;
      $modelInfo = explode( "=", $elementParts[0] );
    }

    qcl_assert_valid_string( $modelInfo[0] );
    qcl_assert_valid_string( $modelInfo[1] );

    $model1 = $this->getElementModel( $modelInfo[0] );
    $model1->load( $modelInfo[1] );

    $model2 = $this->getElementModel( $type );
    $model2->load( $namedId );

    return array( $model1, $model2, $depModel );
  }

  /**
   * Link two model records
   * @param $treeElement
   * @param $type
   * @param $namedId
   * @return string "OK"
   */
  public function actionLink( $treeElement, $type, $namedId )
  {
    $this->requirePermission("access.manage");

    list( $model1, $model2, $depModel ) =
      $this->getLinkModels( $treeElement, $type, $namedId );

    if( $depModel )
    {
      $model1->linkModel( $model2, $depModel );
    }
    else
    {
      $model1->linkModel( $model2 );
    }

    return "OK";
  }

  /**
   * Unlink two model records
   *
   * @param $treeElement
   * @param $type
   * @param $namedId
   * @return string "OK"
   */
  public function actionUnlin( $treeElement, $type, $namedId )
  {
    $this->requirePermission("access.manage");

    list( $model1, $model2, $depModel ) =
      $this->getLinkModels( $treeElement, $type, $namedId );

    if( $depModel )
    {
      $model1->unlinkModel( $model2, $depModel );
    }
    else
    {
      $model1->unlinkModel( $model2 );
    }

    return "OK";
  }

  /**
   * Edit the element data by returning a form to the user
   * @param string|bool $first The type of the element or boolean true
   * @param string $second The namedId of the element
   * @param null|string $third If the first argument is boolean true, then the second and third
   * arguments are the normal signature
   * @internal param $type
   * @internal param $namedId
   * @return array
   */
  public function actionEdit( $first, $second, $third=null )
  {
    /*
     * if first argument is boolean true, this is the call from a
     * dialog
     */
    if ( $first === true )
    {
      $type     = $second;
      $namedId  = $third;
    }

    /*
     * otherwise, normal call
     */
    else
    {
      $type     = $first;
      $namedId  = $second;
    }

    if( $type != "user" or $namedId != $this->getActiveUser()->namedId() )
    {
      $this->requirePermission("access.manage");
    }

    $model = $this->getElementModel( $type );
    $model->load( $namedId );
    $formData = $this->createFormData( $model );

    if ( $type == "user")
    {
      $formData['password']['value'] = null;
      $formData['password2']['value'] = null;
    }

    $modelMap = $this->modelData();
    $message = "<h3>" . Yii::t('app', $modelMap[$type]['dialogLabel'] ) . " '" . $namedId . "'</h3>";

    return \lib\dialog\Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "saveFormData",
      array( $type, $namedId )
    );
  }

  /**
   * Save the form produced by editElement()
   * @param $data
   * @param $type
   * @param $namedId
   * @throws \lib\exceptions\UserErrorException
   * @return \qcl_ui_dialog_Alert|string "OK"
   */
  public function actionSave( $data, $type, $namedId )
  {

    if ( $data === null )
    {
      return "ABORTED";
    }

    if( $type != "user" or $namedId != $this->getActiveUser()->namedId() )
    {
      $this->requirePermission("access.manage");
      $this->dispatchClientMessage("accessControlTool.reloadLeftList");
    }

    /*
     * if we have a password field, we expect to have a password2 field
     * as well to match. return to dialog if passwords do not match.
     */
    if ( isset( $data->password ) and ! empty($data->password) )
    {
      if ( ! isset($data->password2) or $data->password != $data->password2 )
      {
        return \lib\dialog\Alert::create(
          Yii::t('app',"Passwords do not match. Please try again"),
          Yii::$app->controller->id, "editElement", array( "user", $namedId )
        );
      }
    }

    $model = $this->getElementModel( $type );
    $model->load( $namedId );

    /*
     * no ldap user data
     */
    if ( $type == "user" and $model->get("ldap") )
    {
      throw new \Exception(Yii::t('app',"User data is from an LDAP server and cannot be changed."));
    }

    try
    {
      $parsed = (object) $this->parseFormData( $model, $data );
    }
    catch( \lib\exceptions\UserErrorException $e)
    {
      return \lib\dialog\Alert::create(
        $e->getMessage(),
        Yii::$app->controller->id, "editElement", array( "user", $namedId )
      );
    }

    /*
     * set data
     */
    $oldData = (object) $model->data();
    $model->set( $parsed )->save();

    /*
     * user model
     */
    if ( $type == "user" and ! $model->get("ldap") )
    {
      /*
       * enforce setting of password
       */
      if ( ! $data->password and ! $model->getPassword() )
      {
        return \lib\dialog\Alert::create(
          Yii::t('app',"You must set a password."),
          Yii::$app->controller->id, "handleMissingPasswordDialog", array( $namedId )
        );
      }

      /*
       * if password has changed, inform user, unless the old password was a
       * temporary pasword
       */
      if ( $data->password and $parsed->password != $oldData->password and strlen($oldData->password) > 7 )
      {
        return $this->sendInformationEmail( $model->data() );
      }
    }
    return \lib\dialog\Alert::create(Yii::t('app',"The data has been saved."));
  }

  /**
   * Sends an informational email to different groups of 
   * @param $type
   * @param $namedId
   * @return array
   */
  public function actionEmailCompose( $type, $namedId, $subject="", $body="" )
  {
    $this->requirePermission("access.manage");
    
    if( ! in_array( $type, array("user","group") ) )
    {
      throw new \lib\exceptions\UserErrorException("Email can only be sent to users and groups."); 
    }
    
    $model = $this->getElementModel( $type );
    $model->load( $namedId );
    
    $emails = array();
    $names  = array();
    
    switch ( $type )
    {
      case "user":
        $email = $model->getEmail(); 
        if( ! trim( $email ) )
        {
          throw new \lib\exceptions\UserErrorException( Yii::t('app',"The selected user has no email address."));
        }
        $emails[] = $email;
        $names[]  = $model->getName();
        break;
        
      case "group":
        $userModel = $this->getElementModel("user");
        try
        {
          $userModel->findLinked($model);
        }
        catch( qcl_data_model_RecordNotFoundException $e)
        {
          throw new \lib\exceptions\UserErrorException( Yii::t('app',"The selected group has no members."));
        }
        while($userModel->loadNext())
        {
          $email = $userModel->getEmail();
          if( trim($email) ) 
          {
            $emails[] = $email;
            $names[]  = $userModel->getName();
          }
        }
    }

    $number = count($emails);
    if ( $number == 0 )
    {
      throw new \lib\exceptions\UserErrorException( Yii::t('app',"No email address found."));
    }
    
    $modelMap   = $this->modelData();
    $recipients = Yii::t('app', $modelMap[$type]['dialogLabel'] ) . " '" . $model->getName() . "'";
    $message    = "<h3>" . 
                    Yii::t('app', 
                        "Email to %s", 
                        $recipients . ( $type == "group" ? " ($number recipients)" : "") 
                    ) .
                  "</h3>" .
                  ( ( $type == "group" ) ? "<p>" . implode(", ", $names ) . "</p>" : "");
                  
    $formData = array(
      "subject" => array( 
        "label" => Yii::t('app',"Subject"),
        "type"  => "TextField",
        "width" => 400,
        "value" => $subject
      ),
      "body"  => array(
        "label" => Yii::t('app',"Message"),
        "type"  => "TextArea",
        "lines" => 10,
        "value" => $body
      )
    );

    return \lib\dialog\Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "confirmSendEmail",
      array( $this->shelve( $type, $namedId, $emails, $names ) )
    );
  }
  
  public function actionEmailConfirm( $data, $shelfId )
  {

    if ( ! $data )
    {
      $this->unshelve( $shelfId );
      return "CANCELLED"; 
    }

    list( $type, $namedId, $emails, $names ) = $this->unshelve( $shelfId, true );

    if( ! trim($data->subject) )
    {
      return \lib\dialog\Alert::create( 
        Yii::t('app', "Please enter a subject." ),
        Yii::$app->controller->id, "correctEmail",
        array( $shelfId, $data )
      );
    }
    
    if( ! trim($data->body) )
    {
      return \lib\dialog\Alert::create( 
        Yii::t('app', "Please enter a message." ),
        Yii::$app->controller->id, "correctEmail",
        array( $shelfId, $data )
      );
    }
    
    return \lib\dialog\Confirm::create(
      Yii::t('app', "Send email to %s recipients?", count($emails) ), null,
      Yii::$app->controller->id, "sendEmail", 
      array($shelfId, $data)
    );    
  }
  
  public function actionEmailCorrect( $dummy, $shelfId, $data )
  {
    list( $type, $namedId, $emails, $names ) = $this->unshelve( $shelfId );
    return $this->method_composeEmail( $type, $namedId, $data->subject, $data->body );
  }
  
  public function actionEmailSend( $confirm, $shelfId, $data )
  {
    list( $type, $namedId, $emails, $names ) = $this->unshelve( $shelfId );
    
    if ( ! $confirm )
    {
      return "CANCELLED"; 
    }
    
    $subject = $data->subject;
    $body    = $data->body;
    
    foreach( $emails as $index => $email )
    {
      $name = $names[$index];
      $adminEmail  = $this->getApplication()->getIniValue("email.admin");
      $mail = new qcl_util_system_Mail( array(
        'senderEmail'     => $adminEmail,
        'recipient'       => $name,
        'recipientEmail'  => $email,
        'subject'         => $subject,
        'body'            => $body
      ) );
      
      
      $mail->send();
    }

    return \lib\dialog\Alert::create( Yii::t('app', "Sent email to %s recipients", count($emails) ) );
  }


  /**
   * Sends an email to the user, either a confirmation email if email has not yet been confirmed, or an information on
   * change of the password
   * @param array|object $data
   * @return qcl_ui_dialog_Alert
   */
  protected function sendInformationEmail( $data )
  {
    $data = (object) $data;
    if ( ! $data->confirmed )
    {
      $this->sendConfirmationLinkEmail( $data->email, $data->namedId, $data->name );
      return \lib\dialog\Alert::create(
        Yii::t('app',"An email has been sent to %s (%s) with information on the registration.", $data->name, $data->email)
      );
    }
    else
    {
      $this->sendPasswordChangeEmail( $data->email, $data->namedId, $data->name );
      return \lib\dialog\Alert::create(
        Yii::t('app',"An email has been sent to %s (%s) to inform about the change of password.", $data->name, $data->email)
      );
    }
  }

  public function actionMissingPassword( $namedId )
  {
    return $this->edit( "user", $namedId );
  }


  /**
   * Sends an email to confirm the registration
   * @param $email
   * @param $username
   * @param $name
   * @param $tmpPasswd
   */
  protected function sendConfirmationLinkEmail( $email, $username, $name, $tmpPasswd=null )
  {
    $app = $this->getApplication();
    $applicationTitle = $this->getApplicationTitle();
    $adminEmail  = $app->config->getIniValue("email.admin");
    $confirmationLink = qcl_server_JsonRpcRestServer::getJsonRpcRestUrl(
      Yii::$app->controller->id,"confirmEmail", $username
    );

    // compose mail
    $subject = Yii::t('app',"Your registration at %s", $applicationTitle );
    $body  = Yii::t('app',"Dear %s,", $name);
    $body .= "\n\n" . Yii::t('app',"You have been registered as user '%s' at '%s'.", $username, $applicationTitle );
    if( $tmpPasswd )
    {
      $body .= "\n\n" . Yii::t('app', "Your temporary password is '%s'. You will be asked to change it after your first login.", $tmpPasswd);
    }
    $body .= "\n\n" . Yii::t('app',"Please confirm your account by visiting the following link:" );
    $body .= "\n\n" . $confirmationLink;
    $body .= "\n\n" . Yii::t('app',"Thank you." );

    // send mail
    $mail = new qcl_util_system_Mail( array(
      'senderEmail'     => $adminEmail,
      'recipient'       => $name,
      'recipientEmail'  => $email,
      'subject'         => $subject,
      'body'            => $body
    ) );
    $mail->send();
  }

  /**
   * Sends an email with information on the change of password.
   * @param $email
   * @param $username
   * @param $name
   */
  protected function sendPasswordChangeEmail( $email, $username, $name )
  {
    $app = $this->getApplication();
    $applicationTitle = $this->getApplicationTitle();
    $adminEmail  = $app->config->getIniValue("email.admin");

    // compose mail
    $subject = Yii::t('app',"Password change at %s", $applicationTitle );
    $body  = Yii::t('app',"Dear %s,", $name);
    $body .= "\n\n" . Yii::t('app',"This is to inform you that you or somebody else has changed the password at %s.", $applicationTitle );
    $body .= "\n\n" . Yii::t('app',"If this is not what you wanted, please reset your password immediately by clicking on the following link:");
    $body .= "\n\n" . $this->generateResetPasswordURL($email);

    // send email
    $mail = new qcl_util_system_Mail( array(
      'senderEmail'     => $adminEmail,
      'recipient'       => $name,
      'recipientEmail'  => $email,
      'subject'         => $subject,
      'body'            => $body
    ) );
    $mail->send();
  }

  /**
   * Service to confirm a registration via email
   * @param $namedId
   */
  public function actionConfirmRegistration( $namedId )
  {
    $app = $this->getApplication();
    $userModel = $app->getAccessController()->getUserModel();
    header('Content-Type: text/html; charset=utf-8');
    try
    {
      $userModel->findWhere( array(
        'namedId' => $namedId
      ));
      while( $userModel->loadNext() )
      {
        $userModel->set("confirmed", true);
        $userModel->save();
      }
      $msg1 = Yii::t('app', "Thank you, %s, your email address has been confirmed.", $userModel->getName() );
      $msg2 = Yii::t('app',
        "You can now log in as user '%s' at <a href='%s'>this link</a>",
        $userModel->namedId(), $app->getClientUrl()
      );
      echo "<html><p>$msg1<p>";
      echo "<p>$msg2</p></html>";
      exit;
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      // should never be the case
      echo "Invalid username '$namedId'";
      exit;
    }
  }

  /**
   * Displays a dialog to reset the password
   */
  public function actionResetPasswordDialog()
  {
    $msg = Yii::t('app',"Please enter your email address. You will receive a message with a link to reset your password.");
    \lib\dialog\Prompt::create($msg, "", Yii::$app->controller->id, "password-reset-email");
  }

  /**
   * Service to send password reset email
   * @param $email
   * @return string
   * @throws \Exception
   */
  public function actionPasswortResetEmail($email)
  {
    if( $email == false ) return "CANCELLED";

    $userModel = $this->getUserModelFromEmail($email);
    $name = $userModel->get("name");
    $adminEmail = $this->getApplication()->getIniValue("email.admin");
    $applicationTitle = $this->getApplicationTitle();

    // compose mail
    $subject = Yii::t('app',"Password reset at %s", $applicationTitle);
    $body  = Yii::t('app',"Dear %s,", $name);
    $body .= "\n\n" . Yii::t('app',"This is to inform you that you or somebody else has requested a password reset at %s.", $applicationTitle );
    $body .= "\n\n" . Yii::t('app',"If this is not what you wanted, you can ignore this email. Your account is safe.");
    $body .= "\n\n" . Yii::t('app',"If you have requested the reset, please click on the following link:");
    $body .= "\n\n" . $this->generateResetPasswordURL($email);

    // send
    $mail = new qcl_util_system_Mail( array(
      'senderEmail'     => $adminEmail,
      'recipient'       => $name,
      'recipientEmail'  => $email,
      'subject'         => $subject,
      'body'            => $body
    ) );
    $mail->send();

    return \lib\dialog\Alert::create(
      Yii::t('app',"An email has been sent with information on the password reset.")
    );
  }

  /**
   * Service to reset email. Called by a REST request
   * @param $email
   * @param $nonce
   */
  public function actionResetPassword( $email, $nonce )
  {
    $storedNonce = $this->retrieveAndDestroyStoredNonce();
    header('Content-Type: text/html; charset=utf-8');
    if( !$storedNonce or $storedNonce != $nonce )
    {
      echo Yii::t('app',"Access denied.");
      exit;
    }

    // set new temporary password with length 7 (this will enforce a password change)
    $password = qcl_generate_password(7);
    $userModel = $this->getUserModelFromEmail( $email );
    $userModel->set("password", $password )->save();

    // message to the user
    $url = $this->getApplication()->getClientUrl();
    $name = $userModel->getNamedId();
    $msg = Yii::t('app', "%s, your password has been reset.", $userModel->get("name") );
    $msg .= "\n\n" . Yii::t('app', "Your username is '%s' and your temporary password is '%s'.",  $name, $password);
    $msg .= "\n\n" . Yii::t('app', "Please <a href='%s'>log in</a> and change the password now.",  $url);
    echo "<html>" . nl2br($msg) . "</html>";
    exit;
  }

  /**
   * Create nonce and store it in the PHP session
   * @return string The nonce
   */
  protected function createAndStoreNonce()
  {
    $nonce = md5(uniqid(rand(), true));
    $_SESSION['EMAIl_RESET_NONCE'] = $nonce;
    return $nonce;
  }

  /**
   * Retrieves the stored nonce and destroys in the PHP session.
   * @return string
   */
  protected function retrieveAndDestroyStoredNonce()
  {
    $storedNonce = $_SESSION['EMAIl_RESET_NONCE'];
    unset($_SESSION['EMAIl_RESET_NONCE']);
    return $storedNonce;
  }

  /**
   * Returns an URL which can be used to reset the password.
   * @param $email
   * @return string
   */
  protected function generateResetPasswordURL($email)
  {
    return qcl_server_Server::getUrl() .
      "?service="   . Yii::$app->controller->id .
      "&method="    . "resetPassword" .
      "&params="    . $email . "," . $this->createAndStoreNonce() .
      "&sessionId=" . $this->getSessionId();
  }

  /**
   * Returns the (custom) title of the application
   * @return string
   */
  protected function getApplicationTitle()
  {
    return Yii::$app->config->getPreference("application.title");
  }

  /**
   * Given an email address, returns the (first) user record that matches this address
   * @param $email
   * @return qcl_access_model_User
   * @throws \Exception
   */
  protected function getUserModelFromEmail( $email )
  {
    try
    {
      qcl_assert_valid_email($email);
    }
    catch( InvalidArgumentException $e)
    {
      throw new \Exception(
        Yii::t('app',"%s is not a valid email address",$email)
      );
    }
    $userModel = $this->getAccessController()->getUserModel();
    try
    {
      $userModel->loadWhere( array( "email" => $email ) );
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      throw new \Exception(
        Yii::t('app',"No user found for email address %s", $email)
      );
    }
    return $userModel;
  }

  /**
   * Presents the user with a form to enter user data
   */
  public function actionNewUserDialog()
  {
    $message = Yii::t('app',"Please enter the user data. A random password will be generated and sent to the user.");
    $formData = array(
      'namedId'        => array(
        'label'       => Yii::t('app',"Login name"),
        'type'        => "textfield",
        'placeholder' => Yii::t('app',"Enter the short login name"),
        'validation'  => array(
          'required'  => true,
          'validator'   => "string"
        )
      ),
      'name'        => array(
        'type'        => "textfield",
        'label'       => Yii::t('app',"Full name"),
        'placeholder' => Yii::t('app',"Enter the full name of the user"),
        'validation'  => array(
          'required'  => true,
          'validator'   => "string"
        )
      ),
      'email'       => array(
        'type'        => "textfield",
        'label'       => Yii::t('app',"Email address"),
        'placeholder' => Yii::t('app',"Enter a valid Email address"),
        'validation'  => array(
          'required'    => true,
          'validator'   => "email"
        )
      ),
    );

    return \lib\dialog\Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "add-user", array()
    );
  }

  /**
   * Action to add a new user
   *
   * @param object $data
   */
  public function actionAddUser( $data )
  {
    $this->requirePermission("access.manage");

    if ( $data === null ) return "CANCEL";

    qcl_assert_valid_string( $data->namedId, "Invalid login name");

    $model = $this->getElementModel( "user" );

    try
    {
      $model->create( $data->namedId );
      unset( $data->namedId );
    }
    catch ( qcl_data_model_RecordExistsException $e)
    {
      return \lib\dialog\Alert::create( Yii::t('app',"Login name '%s' already exists. Please choose a different one.", $data->namedId ) );
    }

    $model->set( $data )->save();

    // make it a normal user
    $this->getElementModel( "role" )->load(QCL_ROLE_USER)->linkModel($model);

    // generate temporary password
    $tmpPasswd = qcl_generate_password(7);
    $model->set("password", $tmpPasswd )->save();

    $data = (object) $model->data();
    $this->sendConfirmationLinkEmail( $data->email, $data->namedId, $data->name, $tmpPasswd );

    $this->dispatchClientMessage("accessControlTool.reloadLeftList");

    return \lib\dialog\Alert::create(
      Yii::t('app',"An email has been sent to %s (%s) with information on the registration.", $data->name, $data->email)
    );

  }

  /**
   * Presents the user a form to enter the data of a new datasource to be created
   */
  public function actionNewDatasourceDialog()
  {
    $message = Yii::t('app',"Please enter the information on the new datasource.");
    $formData = array(
      'namedId'        => array(
        'label'       => Yii::t('app',"Name"),
        'type'        => "textfield",
        'placeholder' => Yii::t('app',"The short name, e.g. researchgroup1"),
        'validation'  => array(
          'required'  => true,
          'validator'   => "string"
        )
      ),
      'title'        => array(
        'width'       => 500,
        'type'        => "textfield",
        'label'       => Yii::t('app',"Title"),
        'placeholder' => Yii::t('app',"A descriptive title, e.g. Database of Research Group 1"),
        'validation'  => array(
          'required'  => true,
          'validator'   => "string"
        )
      )
    );

    return \lib\dialog\Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "add-datasource", array()
    );
  }

  /**
   * Action to add a new datasource from client-supplied data
   *
   * @param object $data
   */
  public function actionAddDatasource( $data )
  {
    $this->requirePermission("access.manage");

    if ( $data === null ) return "CANCEL";
    $model = $this->getElementModel( "datasource" );

    try {
      $this->getApplication()->createDatasource( $data->namedId, array( 'title' => $data->title ) );
    } catch ( qcl_data_model_RecordExistsException $e)  {
      return \lib\dialog\Alert::create( Yii::t('app',"Datasource name '%s' already exists. Please choose a different one.", $data->namedId ) );
    }

    $this->dispatchClientMessage("accessControlTool.reloadLeftList");
    return \lib\dialog\Alert::create(
      Yii::t('app',
        "Datasource '%s' has been created. By default, it will not be visible to anyone. You have to link it with a group, a role, or a user first.",
        $data->namedId
      )
    );
  }
}
