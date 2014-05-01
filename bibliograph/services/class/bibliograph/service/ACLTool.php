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

/**
 * Backend service class for the access control tool widget
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
   * FIXME Use 'access' datasource!
   * @return array
   */
  protected function modelMap()
  {
    return  array(
      'user'        => array(
        'model'       => $this->getAccessController()->getUserModel(),
        'label'       => $this->tr("Users"),
        'labelProp'   => "name",
        'icon'        => "icon/16/apps/preferences-users.png"
      ),
      'role'        => array(
        'model'       => $this->getAccessController()->getRoleModel(),
        'label'       => $this->tr("Roles"),
        'labelProp'   => "name",
        'icon'        => "icon/16/apps/internet-feed-reader.png"
      ),
      'group'        => array(
        'model'       => $this->getAccessController()->getGroupModel(),
        'label'       => $this->tr("Groups"),
        'labelProp'   => "name",
        'icon'        => "icon/16/actions/address-book-new.png"
      ),
      'permission'  => array(
        'model'       => $this->getAccessController()->getPermissionModel(),
        'label'       => $this->tr("Permissions"),
        'labelProp'   => "namedId",
        'icon'        => "icon/16/apps/preferences-security.png"
      ),
      'datasource'  => array(
        'model'       => $this->getDatasourceModel(),
        'label'       => $this->tr("Datasources"),
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
   * @return unknown_type
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
    return "OK";
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
    switch( $type )
    {
      case "datasource":
        qcl_import("qcl_ui_dialog_Confirm");
        return new qcl_ui_dialog_Confirm(
          $this->tr("Do you want to remove only the datasource entry or all associated data?"),
          array( $this->tr("All data"), $this->tr("Entry only"), true),
          $this->serviceName(), "deleteDatasource", array($ids)
        );

      default:
        foreach ( (array) $ids as $namedId )
        {
          //$models = $this->modelMap();
          $model = $this->getElementModel( $type );
          $model->load( $namedId );
          $model->delete();
        }
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
   * @param $first
   * @param $second
   * @param null $third
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
    $message = "<h3>" . $this->tr( $type ) . " '" . $namedId . "'</h3>";
    qcl_import("qcl_ui_dialog_Form");
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
        qcl_import("qcl_ui_dialog_Alert");
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
      throw new JsonRpcException("LDAP user data cannot be edited.");
    }

    $parsed = (object) $this->parseFormData( $model, $data );

    /*
     * user model:
     * as long as the user is not confirmed, a password must be specified
     * and will be sent to the user
     */
    if ( $type == "user" )
    {
      if ( ! $data->password and ! $model->getPassword() )
      {
        $data->password = $parsed->password = qcl_generate_password(5);
      }
      if ( $data->password and $parsed->password != $model->getPassword() )
      {
        if ( ! $model->get("confirmed") )
        {
          $this->sendConfirmationLinkEmail( $data->email, $namedId, $data->name, $data->password );
          qcl_import("qcl_ui_dialog_Alert");
          new qcl_ui_dialog_Alert(
            $this->tr("An email has been sent to %s (%s) with information on the registration.", $data->name, $data->email)
          );
        }
        else
        {
          $this->sendPasswordChangeEmail( $data->email, $namedId, $data->name, $data->password );
          qcl_import("qcl_ui_dialog_Alert");
          new qcl_ui_dialog_Alert(
            $this->tr("An email has been sent to %s (%s) to inform about the change of password.", $data->name, $data->email)
          );
        }
      }
    }

    /*
     * set data
     */
    $model->set( $parsed );
    $model->save();

    return "OK";
  }

  protected function sendConfirmationLinkEmail( $email, $username, $name, $password )
  {
    $app = $this->getApplication();

    /*
     * mail subject
     */
    $configModel = $app->getConfigModel();
    $applicationTitle =
      $configModel->keyExists("application.title")
        ? $configModel->getKey("application.title")
        : $app->name();
    $subject = $this->tr("Your registration at %s", $applicationTitle );

    /*
     * mail body
     */
    $confirmationLink = qcl_server_Server::getUrl() .
      "?service="   . $this->serviceName() .
      "&method="    . "confirmEmail" .
      "&params="    . $email;

    $body  = $this->tr("Dear %s,", $name);
    $body .= "\n\n" . $this->tr("You have been registered as a user at  '%s'.", $applicationTitle );
    $body .= "\n\n" . $this->tr("Your username is '%s' and your password is '%s'", $username, $password );
    $body .= "\n\n" . $this->tr("Please confirm your account by visiting the following link:" );
    $body .= "\n\n" . $confirmationLink;
    $body .= "\n\n" . $this->tr("Thank you." );

    /*
     * send mail
     */
    qcl_import("qcl_util_system_Mail");
    $adminEmail  = $app->getIniValue("email.admin");
    $mail = new qcl_util_system_Mail( array(
      'senderEmail'     => $adminEmail,
      'recipient'       => $name,
      'recipientEmail'  => $email,
      'subject'         => $subject,
      'body'            => $body
    ) );
    $mail->send();
  }

  protected function sendPasswordChangeEmail( $email, $username, $name, $password )
  {
    $app = $this->getApplication();

    /*
     * mail subject
     */
    $configModel = $app->getConfigModel();
    $applicationTitle =
      $configModel->keyExists("application.title")
        ? $configModel->getKey("application.title")
        : $app->name();
    $subject = $this->tr("Password change at %s", $applicationTitle );

    /*
     * mail body
     */
    $body  = $this->tr("Dear %s,", $name);
    $body .= "\n\n" . $this->tr("This is to inform you that your password has changed at '%s'.", $applicationTitle );
    $body .= "\n\n" . $this->tr("Your username is '%s' and your password is '%s'", $username, $password );

    /*
     * send mail
     */
    qcl_import("qcl_util_system_Mail");
    $adminEmail  = $app->getIniValue("email.admin");
    $mail = new qcl_util_system_Mail( array(
      'senderEmail'     => $adminEmail,
      'recipient'       => $name,
      'recipientEmail'  => $email,
      'subject'         => $subject,
      'body'            => $body
    ) );
    $mail->send();
  }

  public function method_confirmEmail( $email )
  {
    $app = $this->getApplication();
    $userModel = $app->getAccessController()->getUserModel();
    try
    {
      $userModel->findWhere( array(
        'email' => $email
      ));
      while( $userModel->loadNext() )
      {
        $userModel->set("confirmed", true);
        $userModel->save();
      }

      $msg1 = $this->tr( "Thank you, %s, your email address has been confirmed.", $userModel->getName() );
      $msg2 = $this->tr( "You can now log in at <a href='%s'>this link</a>", $app->getClientUrl() );
      echo "<p>$msg1<p>";
      echo "<p>$msg2</p>";
      exit;
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      // should never be the case
      echo "Invalid email $email";
      exit;
    }
  }

}
?>