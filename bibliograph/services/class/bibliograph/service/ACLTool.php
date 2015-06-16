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
qcl_import("qcl_ui_dialog_Alert");
qcl_import("qcl_ui_dialog_Confirm");
qcl_import("qcl_ui_dialog_Prompt");
qcl_import("qcl_ui_dialog_Form");
qcl_import("qcl_util_system_Mail");

/**
 * Backend service class for the access control tool widget
 * @todo move back to qcl
 */
class bibliograph_service_ACLTool
  extends qcl_data_controller_Controller
{

  /*
  --------------------------------------------------------------------
    Editing access object data
  --------------------------------------------------------------------
   */

  /**
   * Returns a map of data on the models that are used for the various xxxElement
   * methods
   * @todo Use 'access' datasource!
   * @return array
   */
  protected function modelMap()
  {
    return  array(
      'user'        => array(
        'model'       => $this->getAccessController()->getUserModel(),
        'label'       => $this->tr("Users"),
        'dialogLabel' => $this->tr("User"),
        'labelProp'   => "name",
        'icon'        => "icon/16/apps/preferences-users.png"
      ),
      'role'        => array(
        'model'       => $this->getAccessController()->getRoleModel(),
        'label'       => $this->tr("Roles"),
        'dialogLabel' => $this->tr("Role"),
        'labelProp'   => "name",
        'icon'        => "icon/16/apps/internet-feed-reader.png"
      ),
      'group'        => array(
        'model'       => $this->getAccessController()->getGroupModel(),
        'label'       => $this->tr("Groups"),
        'dialogLabel' => $this->tr("Group"),
        'labelProp'   => "name",
        'icon'        => "icon/16/actions/address-book-new.png"
      ),
      'permission'  => array(
        'model'       => $this->getAccessController()->getPermissionModel(),
        'label'       => $this->tr("Permissions"),
        'dialogLabel' => $this->tr("Permission"),
        'labelProp'   => "namedId",
        'icon'        => "icon/16/apps/preferences-security.png"
      ),
      'datasource'  => array(
        'model'       => $this->getDatasourceModel(),
        'label'       => $this->tr("Datasources"),
        'dialogLabel' => $this->tr("Datasource"),
        'labelProp'   => "title",
        'icon'        => "icon/16/apps/internet-transfer.png"
      )
    );
  }

  /**
   * Retuns ListItem data for the types of access models
   *
   * @return array
   */
  public function method_getAccessElementTypes()
  {
    $this->requirePermission("access.manage");
    $models = $this->modelMap();
    return array(
      array(
        'icon'    => $models['user']['icon'],
        'label'   => $this->tr("Users"),
        'value'   => $this->marktr("user")
      ),
      array(
        'icon'    => $models['role']['icon'],
        'label'   => $this->tr("Roles"),
        'value'   => $this->marktr("role")
      ),
      array(
        'icon'    => $models['group']['icon'],
        'label'   => $this->tr("Groups"),
        'value'   => $this->marktr("group")
      ),
      array(
        'icon'    => $models['permission']['icon'],
        'label'   => $this->tr("Permissions"),
        'value'   => $this->marktr("permission")
      ),
      array(
        'icon'    => $models['datasource']['icon'],
        'label'   => $this->tr("Datasources"),
        'value'   => $this->marktr("datasource")
      ),
    );
  }

  /**
   * Return ListItem data for access models
   *
   * @param $type
   * @throws JsonRpcException
   * @return array
   */
  public function method_getAccessElements( $type )
  {
    $this->requirePermission("access.manage");
    $activeUser   = $this->getActiveUser();
    $isAdmin      = $activeUser->hasRole( QCL_ROLE_ADMIN );

    switch ( $type )
    {
      case "user":
        $model = $this->getAccessController()->getUserModel();
        $labelProp = "name";
        $model->findWhere( array( 'anonymous' => false ),"name" );
        break;
      case "role":
        $model = $this->getAccessController()->getRoleModel();
        $labelProp = "name";
        $model->findAllOrderBy( $labelProp );
        break;
      case "group":
        $model = $this->getAccessController()->getGroupModel();
        $labelProp = "name";
        $model->findAllOrderBy( $labelProp );
        break;
      case "permission":
        $model = $this->getAccessController()->getPermissionModel();
        $labelProp = "namedId";
        $model->findAllOrderBy( $labelProp );
        break;
      case "datasource":
        $model = $this->getDatasourceModel();
        $labelProp = "title";
        $model->findAllOrderBy( $labelProp );
        break;
      default:
        throw new JsonRpcException("Invalid type $type");
    }

    $result = array();
    $models = $this->modelMap();
    while( $model->loadNext() )
    {
      $value  = $model->namedId();

//      /*
//       * don't show hidden records
//       */
//      if( $model->has("hidden" ) )
//      {
//        if( $model->get("hidden") )
//        {
//          continue;
//        }
//      }

      $icon   = $models[$type]['icon'];
      $label  = $model->get($labelProp);

      if ( $model->hasProperty("hidden") and $model->isHidden() and ! $isAdmin )
      {
        continue;
      }

      if ( ! trim($label ) )
      {
        $label = $value;
      }

      if ( $model->hasProperty("ldap") and $model->getLdap() )
      {
        $label .= " (LDAP)";
      }

      if ( $type == "permission" )
      {
        $description = $model->getDescription();
        if ( $description )
        {
          $label .= sprintf( " (%s)", $description );
        }
      }

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
   * Returns the model of a given element type
   * @param string $type
   * @throws JsonRpcException
   * @return qcl_data_model_AbstractActiveRecord
   */
  protected function getElementModel( $type )
  {
    $models = $this->modelMap();
    if ( isset( $models[$type] ) )
    {
      return $models[$type]['model'];
    }
    throw new JsonRpcException( "Invalid type '$type'" );
  }

  /**
   * Returns the tree of model relationships based on the selected element
   * @param $elementType
   * @param $namedId
   * @throws JsonRpcException
   * @return array
   */
  public function method_getAccessElementTree( $elementType, $namedId )
  {
    $this->requirePermission("access.manage");

    $models = $this->modelMap();

    /*
     * top node
     */
    $tree = array(
      'icon'      => "icon/16/apps/utilities-network-manager.png",
      'children'  => array(),
      'label'     => $this->tr("Relations"),
      'value'     => null,
      'type'      => null
    );

    /*
     * the edited model element
     */
    $thisModel = $this->getElementModel( $elementType );
    if( ! $thisModel )
    {
      throw new JsonRpcException("Invalid type argument $elementType");
    }
    $thisModel->load( $namedId );

    /*
     * iterate through the models and display relations as
     * tree structure
     */
    foreach( $models as $type => $data )
    {

      $model = $data['model'];

      if ( $thisModel->hasRelationWithModel( $model )  )
      {

        $node = array(
          'icon'      => $data['icon'],
          'label'     => $data['label'],
          'value'     => $elementType . "=" . $namedId,
          'type'      => $type,
          'mode'      => "link",
          'children'  => array()
        );

        /*
         * special case role - users: skip, would have to be
         * displayed in dependenc of group, we leave this
         * to user - roles
         */
        if( $thisModel instanceof $models['role']['model']
            and $model instanceof $models['user']['model'] )
        {
          continue;
        }

        /*
         * special case: user - role, which can be dependent on the group
         */
        elseif( $thisModel instanceof $models['user']['model']
            and $model instanceof $models['role']['model'] )
        {
          $userModel  = $thisModel;
          $roleModel  = $model;
          $groupModel = $models['group']['model'];

          /*
           * you cannot link to this node
           */
          $node['mode'] = null;

          /*
           * find all groups that the user is member of
           */
          try
          {
            $groupModel->findLinked( $userModel );

            while( $groupModel->loadNext() )
            {
              $groupNode = array(
                'icon'      => $models['group']['icon'],
                'label'     => $this->tr("in") . " " . $groupModel->get( $models['group']['labelProp'] ),
                'type'      => "role",
                'mode'      => "link",
                'value'     => "group=" . $groupModel->namedId() . ",user=" . $userModel->namedId(),
                'children'  => array()
              );
              try
              {
                $roleModel->findLinked( $userModel, $groupModel );
                while( $roleModel->loadNext() )
                {
                  $label = $roleModel->get( $models['role']['labelProp'] );
                  $roleNode = array(
                    'icon'      => $models['role']['icon'],
                    'label'     => either( $label,$model->namedId()),
                    'type'      => "role",
                    'mode'      => "unlink",
                    'value'     => "group=" . $groupModel->namedId() . ",role=" . $roleModel->namedId(),
                    'children'  => array()
                  );
                  $groupNode['children'][] = $roleNode;
                }
              }
              catch( qcl_data_model_RecordNotFoundException $e) {}

              /*
               * add group node to roles node
               */
              $node['children'][] = $groupNode;
            }
          }
          catch( qcl_data_model_RecordNotFoundException $e ){}

          /*
           * no group dependency
           */
          $groupNode = array(
            'icon'      => $models['group']['icon'],
            'label'     => $this->tr("In all groups"),
            'type'      => "role",
            'value'     => "user=" . $userModel->namedId(),
            'mode'      => "link",
            'children'  => array()
          );

          /*
           * find all roles that are linked to the user
           * but not dendent on a group
           */
          try
          {
            $query = $roleModel->findLinkedNotDepends( $userModel, $groupModel );

            while( $roleModel->loadNext( $query ) )
            {
              $label = $roleModel->get( $models['role']['labelProp'] );
              $roleNode = array(
                'icon'      => $models['role']['icon'],
                'label'     => either( $label, $roleModel->namedId() ),
                'type'      => "role",
                'mode'      => "unlink",
                'value'     => "role=" . $roleModel->namedId(),
                'children'  => array()
              );
              $groupNode['children'][] = $roleNode;
            }
          }
          catch( qcl_data_model_RecordNotFoundException $e) {}

          /*
           * add group node to roles node
           */
          $node['children'][] = $groupNode;
        }

        /*
         * no dependencies
         */
        else
        {
          try
          {
            $model->findLinked( $thisModel );

            while( $model->loadNext() )
            {
              $label = $model->get($data['labelProp']);
              $node['children'][] = array(
                'icon'      => $data['icon'],
                'label'     => either( $label,  $model->namedId() ),
                'type'      => $type,
                'value'     => $type . "=" . $model->namedId(),
                'mode'      => "unlink",
                'children'  => array()
              );
            }
          }
          catch( qcl_data_model_RecordNotFoundException $e) {}
        }
        $tree['children'][] = $node;
      }
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
  public function method_addElement( $type, $namedId )
  {
    $this->requirePermission("access.manage");
    $models = $this->modelMap();

    if ( $type == "datasource" )
    {
      qcl_import( "qcl_data_datasource_Manager" );
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
        $models[$type]['labelProp'] => $namedId
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
  public function method_deleteElement( $type, $ids )
  {
    $this->requirePermission("access.manage");
    $minId = null;
    switch( $type )
    {
      case "datasource":
        return new qcl_ui_dialog_Confirm(
          $this->tr("Do you want to remove only the datasource entry or all associated data?"),
          array( $this->tr("All data"), $this->tr("Entry only"), true),
          $this->serviceName(), "deleteDatasource", array($ids)
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
      //$models = $this->modelMap();
      $model = $this->getElementModel( $type );
      $model->load( $namedId );
      if( $minId and $model->id() < $minId )
      {
        throw new qcl_server_ServiceException( $this->tr("Deleting element '%s' of type '%s' is not allowed.", $namedId, $type));
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
   * @return qcl_ui_dialog_Alert
   */
  public function method_deleteDatasource( $doDeleteModelData, $namedId )
  {
    if ( $doDeleteModelData === null )
    {
      return "ABORTED";
    }

    $this->requirePermission("access.manage");
    qcl_import("qcl_ui_dialog_Alert");

    try
    {
      qcl_assert_boolean( $doDeleteModelData );
      qcl_import( "qcl_data_datasource_Manager" );
      qcl_data_datasource_Manager::getInstance()->deleteDatasource( $namedId, $doDeleteModelData );
      $this->broadcastClientMessage("accessControlTool.reloadLeftList");
    }
    catch ( PDOException $e )
    {
      $this->warn(  $e->getMessage() );
      return new  qcl_ui_dialog_Alert($this->tr("Deleting datasource '%s' failed... ",$namedId));
    }

    return new  qcl_ui_dialog_Alert($this->tr("Datasource '%s' successfully deleted ... ",$namedId));
  }

  protected function getLinkModels( $treeElement, $type, $namedId )
  {
    //$models = $this->modelMap();

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
  public function method_linkElements( $treeElement, $type, $namedId )
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
  public function method_unlinkElements( $treeElement, $type, $namedId )
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
  public function method_editElement( $first, $second, $third=null )
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

    $modelMap = $this->modelMap();
    $message = "<h3>" . $this->tr( $modelMap[$type]['dialogLabel'] ) . " '" . $namedId . "'</h3>";

    return new qcl_ui_dialog_Form(
      $message, $formData, true,
      $this->serviceName(), "saveFormData",
      array( $type, $namedId )
    );
  }

  /**
   * Save the form produced by editElement()
   * @param $data
   * @param $type
   * @param $namedId
   * @throws JsonRpcException
   * @return \qcl_ui_dialog_Alert|string "OK"
   */
  public function method_saveFormData( $data, $type, $namedId )
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
        return new qcl_ui_dialog_Alert(
          $this->tr("Passwords do not match. Please try again"),
          $this->serviceName(), "editElement", array( "user", $namedId )
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
      throw new qcl_server_ServiceException($this->tr("User data is from an LDAP server and cannot be changed."));
    }

    try
    {
      $parsed = (object) $this->parseFormData( $model, $data );
    }
    catch( JsonRpcException $e)
    {
      return new qcl_ui_dialog_Alert(
        $e->getMessage(),
        $this->serviceName(), "editElement", array( "user", $namedId )
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
        return new qcl_ui_dialog_Alert(
          $this->tr("You must set a password."),
          $this->serviceName(), "handleMissingPasswordDialog", array( $namedId )
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
    return new qcl_ui_dialog_Alert($this->tr("The data has been saved."));
  }

  /**
   * Sends an informational email to different groups of 
   * @param $type
   * @param $namedId
   * @return array
   */
  public function method_composeEmail( $type, $namedId, $subject="", $body="" )
  {
    $this->requirePermission("access.manage");
    
    if( ! in_array( $type, array("user","group") ) )
    {
      throw new JsonRpcException("Email can only be sent to users and groups."); 
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
          throw new JsonRpcException( $this->tr("The selected user has no email address."));
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
          throw new JsonRpcException( $this->tr("The selected group has no members."));
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
      throw new JsonRpcException( $this->tr("No email address found."));
    }
    
    $modelMap   = $this->modelMap();
    $recipients = $this->tr( $modelMap[$type]['dialogLabel'] ) . " '" . $model->getName() . "'";
    $message    = "<h3>" . 
                    $this->tr( 
                        "Email to %s", 
                        $recipients . ( $type == "group" ? " ($number recipients)" : "") 
                    ) .
                  "</h3>" .
                  ( ( $type == "group" ) ? "<p>" . implode(", ", $names ) . "</p>" : "");
                  
    $formData = array(
      "subject" => array( 
        "label" => $this->tr("Subject"),
        "type"  => "TextField",
        "width" => 400,
        "value" => $subject
      ),
      "body"  => array(
        "label" => $this->tr("Message"),
        "type"  => "TextArea",
        "lines" => 10,
        "value" => $body
      )
    );

    return new qcl_ui_dialog_Form(
      $message, $formData, true,
      $this->serviceName(), "confirmSendEmail",
      array( $this->shelve( $type, $namedId, $emails, $names ) )
    );
  }
  
  public function method_confirmSendEmail( $data, $shelfId )
  {

    if ( ! $data )
    {
      $this->unshelve( $shelfId );
      return "CANCELLED"; 
    }

    list( $type, $namedId, $emails, $names ) = $this->unshelve( $shelfId, true );

    if( ! trim($data->subject) )
    {
      return new qcl_ui_dialog_Alert( 
        $this->tr( "Please enter a subject." ),
        $this->serviceName(), "correctEmail",
        array( $shelfId, $data )
      );
    }
    
    if( ! trim($data->body) )
    {
      return new qcl_ui_dialog_Alert( 
        $this->tr( "Please enter a message." ),
        $this->serviceName(), "correctEmail",
        array( $shelfId, $data )
      );
    }
    
    return new qcl_ui_dialog_Confirm(
      $this->tr( "Send email to %s recipients?", count($emails) ), null,
      $this->serviceName(), "sendEmail", 
      array($shelfId, $data)
    );    
  }
  
  public function method_correctEmail( $dummy, $shelfId, $data )
  {
    list( $type, $namedId, $emails, $names ) = $this->unshelve( $shelfId );
    return $this->method_composeEmail( $type, $namedId, $data->subject, $data->body );
  }
  
  public function method_sendEmail( $confirm, $shelfId, $data )
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

    return new qcl_ui_dialog_Alert( $this->tr( "Sent email to %s recipients", count($emails) ) );
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
      return new qcl_ui_dialog_Alert(
        $this->tr("An email has been sent to %s (%s) with information on the registration.", $data->name, $data->email)
      );
    }
    else
    {
      $this->sendPasswordChangeEmail( $data->email, $data->namedId, $data->name );
      return new qcl_ui_dialog_Alert(
        $this->tr("An email has been sent to %s (%s) to inform about the change of password.", $data->name, $data->email)
      );
    }
  }

  public function method_handleMissingPasswordDialog( $namedId )
  {
    return $this->method_editElement( "user", $namedId );
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
    $adminEmail  = $app->getIniValue("email.admin");
    $confirmationLink = qcl_server_Server::getUrl() .
      "?service="   . $this->serviceName() .
      "&method="    . "confirmEmail" .
      "&params="    . $username;

    // compose mail
    $subject = $this->tr("Your registration at %s", $applicationTitle );
    $body  = $this->tr("Dear %s,", $name);
    $body .= "\n\n" . $this->tr("You have been registered as user '%s' at '%s'.", $username, $applicationTitle );
    if( $tmpPasswd )
    {
      $body .= "\n\n" . $this->tr( "Your temporary password is '%s'. You will be asked to change it after your first login.", $tmpPasswd);
    }
    $body .= "\n\n" . $this->tr("Please confirm your account by visiting the following link:" );
    $body .= "\n\n" . $confirmationLink;
    $body .= "\n\n" . $this->tr("Thank you." );

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
    $adminEmail  = $app->getIniValue("email.admin");

    // compose mail
    $subject = $this->tr("Password change at %s", $applicationTitle );
    $body  = $this->tr("Dear %s,", $name);
    $body .= "\n\n" . $this->tr("This is to inform you that you or somebody else has changed the password at %s.", $applicationTitle );
    $body .= "\n\n" . $this->tr("If this is not what you wanted, please reset your password immediately by clicking on the following link:");
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
  public function method_confirmEmail( $namedId )
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
      $msg1 = $this->tr( "Thank you, %s, your email address has been confirmed.", $userModel->getName() );
      $msg2 = $this->tr(
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
   * @return qcl_ui_dialog_Prompt
   */
  public function method_resetPasswordDialog()
  {
    $msg = $this->tr("Please enter your email address. You will receive a message with a link to reset your password.");
    return new qcl_ui_dialog_Prompt($msg, "", $this->serviceName(), "sendPasswortResetEmail");
  }

  /**
   * Service to send password reset email
   * @param $email
   * @return string
   * @throws qcl_server_ServiceException
   */
  public function method_sendPasswortResetEmail($email)
  {
    if( $email == false ) return "CANCELLED";

    $userModel = $this->getUserModelFromEmail($email);
    $name = $userModel->get("name");
    $adminEmail = $this->getApplication()->getIniValue("email.admin");
    $applicationTitle = $this->getApplicationTitle();

    // compose mail
    $subject = $this->tr("Password reset at %s", $applicationTitle);
    $body  = $this->tr("Dear %s,", $name);
    $body .= "\n\n" . $this->tr("This is to inform you that you or somebody else has requested a password reset at %s.", $applicationTitle );
    $body .= "\n\n" . $this->tr("If this is not what you wanted, you can ignore this email. Your account is safe.");
    $body .= "\n\n" . $this->tr("If you have requested the reset, please click on the following link:");
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

    return new qcl_ui_dialog_Alert(
      $this->tr("An email has been sent with information on the password reset.")
    );
  }

  /**
   * Service to reset email. Called by a REST request
   * @param $email
   * @param $nonce
   */
  public function method_resetPassword( $email, $nonce )
  {
    $storedNonce = $this->retrieveAndDestroyStoredNonce();
    header('Content-Type: text/html; charset=utf-8');
    if( !$storedNonce or $storedNonce != $nonce )
    {
      echo $this->tr("Access denied.");
      exit;
    }

    // set new temporary password with length 7 (this will enforce a password change)
    $password = qcl_generate_password(7);
    $userModel = $this->getUserModelFromEmail( $email );
    $userModel->set("password", $password )->save();

    // message to the user
    $url = $this->getApplication()->getClientUrl();
    $name = $userModel->getNamedId();
    $msg = $this->tr( "%s, your password has been reset.", $userModel->get("name") );
    $msg .= "\n\n" . $this->tr( "Your username is '%s' and your temporary password is '%s'.",  $name, $password);
    $msg .= "\n\n" . $this->tr( "Please <a href='%s'>log in</a> and change the password now.",  $url);
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
      "?service="   . $this->serviceName() .
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
    $app = $this->getApplication();
    $configModel = $app->getConfigModel();
    return
      $configModel->keyExists("application.title")
        ? $configModel->getKey("application.title")
        : $app->name();
  }

  /**
   * Given an email address, returns the (first) user record that matches this address
   * @param $email
   * @return qcl_access_model_User
   * @throws qcl_server_ServiceException
   */
  protected function getUserModelFromEmail( $email )
  {
    try
    {
      qcl_assert_valid_email($email);
    }
    catch( InvalidArgumentException $e)
    {
      throw new qcl_server_ServiceException(
        $this->tr("%s is not a valid email address",$email)
      );
    }
    $userModel = $this->getAccessController()->getUserModel();
    try
    {
      $userModel->loadWhere( array( "email" => $email ) );
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      throw new qcl_server_ServiceException(
        $this->tr("No user found for email address %s", $email)
      );
    }
    return $userModel;
  }

  public function method_newUserDialog()
  {
    $message = $this->tr("Please enter the user data. A random password will be generated and sent to the user.");
    $formData = array(
      'namedId'        => array(
        'label'       => $this->tr("Login name"),
        'type'        => "textfield",
        'placeholder' => $this->tr("Enter the short login name"),
        'validation'  => array(
          'required'  => true,
          'validator'   => "string"
        )
      ),
      'name'        => array(
        'type'        => "textfield",
        'label'       => $this->tr("Full name"),
        'placeholder' => $this->tr("Enter the full name of the user"),
        'validation'  => array(
          'required'  => true,
          'validator'   => "string"
        )
      ),
      'email'       => array(
        'type'        => "textfield",
        'label'       => $this->tr("Email address"),
        'placeholder' => $this->tr("Enter a valid Email address"),
        'validation'  => array(
          'required'    => true,
          'validator'   => "email"
        )
      ),
    );

    return new qcl_ui_dialog_Form(
      $message, $formData, true,
      $this->serviceName(), "addNewUser", array()
    );
  }

  public function method_addNewUser( $data )
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
      return new qcl_ui_dialog_Alert( $this->tr("Login name '%s' already exists. Please choose a different one.", $data->namedId ) );
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

    return new qcl_ui_dialog_Alert(
      $this->tr("An email has been sent to %s (%s) with information on the registration.", $data->name, $data->email)
    );

  }

  public function method_newDatasourceDialog()
  {
    $message = $this->tr("Please enter the information on the new datasource.");
    $formData = array(
      'namedId'        => array(
        'label'       => $this->tr("Name"),
        'type'        => "textfield",
        'placeholder' => $this->tr("The short name, e.g. researchgroup1"),
        'validation'  => array(
          'required'  => true,
          'validator'   => "string"
        )
      ),
      'title'        => array(
        'width'       => 500,
        'type'        => "textfield",
        'label'       => $this->tr("Title"),
        'placeholder' => $this->tr("A descriptive title, e.g. Database of Research Group 1"),
        'validation'  => array(
          'required'  => true,
          'validator'   => "string"
        )
      )
    );

    return new qcl_ui_dialog_Form(
      $message, $formData, true,
      $this->serviceName(), "addNewDatasource", array()
    );
  }

  public function method_addNewDatasource( $data )
  {
    $this->requirePermission("access.manage");

    if ( $data === null ) return "CANCEL";

    qcl_assert_valid_string( $data->namedId, "Invalid datasource name");

    $model = $this->getElementModel( "datasource" );

    try
    {
      $this->getApplication()->createDatasource( $data->namedId, array( 'title' => $data->title ) );
    }
    catch ( qcl_data_model_RecordExistsException $e)
    {
      return new qcl_ui_dialog_Alert( $this->tr("Datasource name '%s' already exists. Please choose a different one.", $data->namedId ) );
    }

    $this->dispatchClientMessage("accessControlTool.reloadLeftList");
    return new qcl_ui_dialog_Alert(
      $this->tr(
        "Datasource '%s' has been created. By default, it will not be visible to anyone. You have to link it with a group, a role, or a user first.",
        $data->namedId
      )
    );

  }
}
