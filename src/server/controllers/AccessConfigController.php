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

use app\models\Group;
use lib\dialog\Alert;
use lib\exceptions\UserErrorException;
use Yii;


/**
 * Backend service class for the access control tool widget
 */
class AccessConfigController extends \app\Controllers\AppController
{
  /**
   * Returns a map of data on the models that are used for the various xxxElement
   * methods
   * @return array
   */
  public function getModelData()
  {
    /** @noinspection MissedFieldInspection */
    return [
      'user' => [
        'class' => \app\models\User::class,
        'label' => Yii::t('app', "Users"),
        'dialogLabel' => Yii::t('app', "User"),
        'labelProp' => "name",
        'icon' => "icon/16/apps/preferences-users.png"
      ],
      'role' => [
        'class' => \app\models\Role::class,
        'label' => Yii::t('app', "Roles"),
        'dialogLabel' => Yii::t('app', "Role"),
        'labelProp' => "name",
        'icon' => "icon/16/apps/internet-feed-reader.png"
      ],
      'group' => [
        'class' => \app\models\Group::class,
        'label' => Yii::t('app', "Groups"),
        'dialogLabel' => Yii::t('app', "Group"),
        'labelProp' => "name",
        'icon' => "icon/16/actions/address-book-new.png"
      ],
      'permission' => [
        'class' => \app\models\Permission::class,
        'label' => Yii::t('app', "Permissions"),
        'dialogLabel' => Yii::t('app', "Permission"),
        'labelProp' => "namedId",
        'icon' => "icon/16/apps/preferences-security.png"
      ],
      'datasource' => [
        'class' => \app\models\Datasource::class,
        'label' => Yii::t('app', "Datasources"),
        'dialogLabel' => Yii::t('app', "Datasource"),
        'labelProp' => "title",
        'icon' => "icon/16/apps/internet-transfer.png"
      ]
    ];
  }

  /*
  ---------------------------------------------------------------------------
     Protected auxiliary methods
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the model of the given type, with the record data of the given namedId
   * @param string $type
   * @param string $namedId
   * @return \lib\models\BaseModel
   */
  protected function getModelInstance($type, $namedId)
  {
    $modelClass = $this->getModelDataFor($type)['class'];
    return $modelClass::findByNamedId($namedId);
  }

  /**
   * Return data from the model map pertaining to the model type
   *
   * @param string $ype
   * @return array
   * @throws \InvalidArgumentException
   */
  protected function getModelDataFor($type)
  {
    if (!isset($this->modelData[$type])) {
      throw new \InvalidArgumentException("Invalid type '$type'");
    }
    return $this->modelData[$type];
  }

  /**
   * Returns the class name for the model type
   *
   * @param string $ype
   * @return string
   * @throws \InvalidArgumentException
   */
  protected function getModelClassFor($type)
  {
    return $this->getModelDataFor($type)['class'];
  }

  /**
   * Return the models that are linked to the currently
   * selected tree element
   *
   * @param string $treeElement
   *    A string consisting of type=namedId pairs, separated by commas, defining
   *    what models the tree elements connects
   * @param string $type
   *    The type of the current element
   * @param string $namedId
   *    The named id of the current element
   * @param bool $link
   *    If true, link the models, if false, unlink them
   * @return void
   * @throws \InvalidArgumentException
   */
  protected function linkOrUnlink($treeElement, $type, $namedId, $link=true )
  {
    $elementParts = explode(",", $treeElement);
    $extraColumns = null;
    if (count($elementParts) > 1) {
      // we have a dependent model
      list($depModelType, $depModelNamedId) = explode("=", $elementParts[0]);
      $linkedModelInfo = explode("=", $elementParts[1]);
      /** @var \lib\models\BaseModel $depModel */
      $depModel     = $this->getModelInstance(trim($depModelType), trim($depModelNamedId));
      if( $depModelType != "group" ) {
        throw new \InvalidArgumentException("Invalid dependent model type '$depModelType'");
      }
      $extraColumns = [ 'GroupId' => $depModel->id ];
    } else {
      $depModelArray = null;
      $linkedModelInfo = explode("=", $elementParts[0]);
    }
    $model = $this->getModelInstance($type, $namedId);
    $linkedModelRelation = trim($linkedModelInfo[0]) . "s";
    $linkedModelNamedId  = trim($linkedModelInfo[1]);
    $linkedModel         = $this->getModelInstance($linkedModelRelation, $linkedModelNamedId);
    if( $link ){
      $model->link( $linkedModelRelation, $linkedModel, $extraColumns );
    } else {
      $model->unlink( $linkedModelRelation, $linkedModel, $extraColumns );
    }
  }

  /*
  ---------------------------------------------------------------------------
     ACTIONS
  ---------------------------------------------------------------------------
  */


  /**
   * Retuns ListItem data for the types of access models
   * @jsonrpc access-config/types
   */
  public function actionTypes()
  {
    $modelData = $this->modelData;
    return [
      [
        'icon' => $modelData['user']['icon'],
        'label' => Yii::t('app', "Users"),
        'value' => "user"
      ],
      [
        'icon' => $modelData['role']['icon'],
        'label' => Yii::t('app', "Roles"),
        'value' => "role"
      ],
      [
        'icon' => $modelData['group']['icon'],
        'label' => Yii::t('app', "Groups"),
        'value' => "group"
      ],
      [
        'icon' => $modelData['permission']['icon'],
        'label' => Yii::t('app', "Permissions"),
        'value' => "permission"
      ],
      [
        'icon' => $modelData['datasource']['icon'],
        'label' => Yii::t('app', "Datasources"),
        'value' => "datasource"
      ],
    ];
  }

  /**
   * Return ListItem data for access models
   *
   * @jsonrpc access-config/elements
   * @param string $type
   *    The type of the element
   * @param array|null $filter
   *    An associative array that can be used in a ActiveQuery::where() method call
   * @throws \lib\exceptions\UserErrorException
   * @throws \JsonRpc2\Exception
   * @throws \InvalidArgumentException
   */
  public function actionElements($type, array $filter = null)
  {
    $this->requirePermission("access.manage");
    $activeUser = $this->getActiveUser();
    $isAdmin = $activeUser->hasRole("admin");
    // query
    $elementData = $this->getModelDataFor($type);
    $modelClass = $elementData['class'];
    $labelProp = "name";
    /* @var \yii\db\ActiveQuery $query */
    switch ($type) {
      case "user":
        $query = $modelClass::find()->where(['anonymous' => false]);
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
    if ($filter) {
      try {
        $query = $query->where($filter);
      } catch (\Exception $e) {
        throw new UserErrorException("Invalid filter");
      }
    }
    $records = $query->all();
    $elementData = $this->getModelDataFor($type);
    // create result from record data
    $result = [];
    //Yii::trace($elementData);
    foreach ($records as $record) {
      $value = $record->namedId;
      $label = $record->$labelProp;
      $icon = $elementData['icon'];
      // special cases
      if ($record->hasAttribute("hidden") and $record->hidden and !$isAdmin) continue;
      if (!trim($label)) $label = $value;
      if ($record->hasAttribute("ldap") and $record->ldap) $label .= " (LDAP)";
      if ($type == "permission") {
        $description = $record->description;
        if ($description) {
          $label .= sprintf(" (%s)", $description);
        }
      }
      // entry
      $result[] = array(
        'icon' => $icon,
        'label' => $label,
        'params' => $type . "," . $value,
        'type' => $type,
        'value' => $value
      );
    }
    return $result;
  }

  /**
   * Returns the tree of model relationships based on the selected element
   * @param $elementType
   * @param $namedId
   * @throws \lib\exceptions\UserErrorException
   * @throws \JsonRpc2\Exception
   */
  public function actionTree($elementType, $namedId)
  {
    $this->requirePermission("access.manage");
    $modelData = $this->modelData;

    // the edited model element
    $elementData = $this->getModelDataFor($elementType);
    $modelClass = $elementData['class'];
    /** @var \yii\db\ActiveRecord $model */
    $model = $modelClass::findByNamedId($namedId);
    if (!$model) {
      throw new UserErrorException("Model of '$elementType' with id '$namedId' does not exist.");
    }

    // root node
    $tree = [
      'icon'   => "icon/16/apps/utilities-network-manager.png",
      'label'  => Yii::t('app', "Relations"),
      'action' => null,
      'value'  => null,
      'type'   => null,
      'children'=> [],
    ];

    // iterate through the rec and display relations as tree structure
    $types = array_keys($this->modelData);
    foreach ($types as $linkedType) {
      // skip if same
      if ($linkedType == $elementType) continue;
      // skip role -> user
      if ($elementType == "role" and $linkedType == "users") continue;
      // skip if no relation
      try {
        $model->getRelation($linkedType . "s"); // this throws if no relation exists
      } catch (\yii\base\InvalidParamException $e) {
        continue;
      }

      // normal node
      $linkedElementdata = $this->getModelDataFor($linkedType);
      $node = array(
        'icon'    => $linkedElementdata['icon'],
        'label'   => $linkedElementdata['label'],
        'type'    => $linkedType,
        'action'  => "link",
        'value'   => $elementType . "=" . $namedId,
        'children' => []
      );

      // user -> roles
      if ($elementType == "user" and $linkedType == "role") {
        /** @var \app\models\User $user */
        $user = $model;
        // you cannot link to this node
        $node['action'] = null;
        $node['value']  = null;

        // pseudo group node -> no group dependency
        $groupNode = [
          'icon'    => $modelData['group']['icon'],
          'label'   => Yii::t('app', "In all groups"),
          'type'    => "group",
          'action'  => "link",
          'value'   => "user=" . $user->namedId,
          'children' => []
        ];
        $roles = $user->getGroupRoles(null)->all();
        foreach ($roles as $role) {
          $roleNode = [
            'icon'    => $modelData['role']['icon'],
            'label'   => $role->name,
            'type'    => "role",
            'action'  => "unlink",
            'value'   => "role=" . $role->namedId,
            'children' => []
          ];
          $groupNode['children'][] = $roleNode;
        }
        $node['children'][] = $groupNode;

        // one node for each existing group
        /** @var \app\models\Group[] $allGroups */
        $allGroups = Group::find()->where(['not', ['active' => null]])->all(); // @todo where active=1
        foreach ($allGroups as $group) {
          $groupNode = array(
            'icon'    => $modelData['group']['icon'],
            'label'   => Yii::t('app', "in") . " " . $group->name,
            'type'    => "group",
            'action'  => "link",
            'value'   => "group=" . $group->namedId . ",user=" . $user->namedId,
            'children' => []
          );
          /** @var \app\models\Role[] $roles */
          $roles = $user->getGroupRoles(null)->all();
          foreach ($roles as $role) {
            $roleNode = array(
              'icon'    => $modelData['role']['icon'],
              'label'   => $role->name,
              'type'    => "role",
              'action'  => "unlink",
              'value'   => "group=" . $group->namedId . ",role=" . $role->namedId,
              'children' => []
            );
            $groupNode['children'][] = $roleNode;
          }
          $node['children'][] = $groupNode;
        }
      } else {
        // other combinations
        $relation = $linkedType . "s";
        /** @var \lib\models\BaseModel $linkedModel */
        foreach ($model->$relation as $linkedModel) {
          $linkedNode = [
            'icon'    => $modelData[$linkedType]['icon'],
            'label'   => $linkedModel->getAttribute($modelData[$linkedType]['label']),
            'type'    => $linkedType,
            'action'  => "unlink",
            'value'   => "$linkedType=" . $linkedModel->namedId,
            'children' => []
          ];
          $node['children'][] = $linkedNode;
        }
      }
      $tree['children'][] = $node;
    }
    return $tree;
  }

  /**
   * Add an empty model record. When creating a datasource,
   * a default bibliograph datasource is created.
   * Creates the form editor
   *
   * @param string $type
   * @param string $namedId
   * @throws \JsonRpc2\Exception
   * @todo Implement support for other datasource types
   */
  public function actionAdd($type, $namedId)
  {
    $this->requirePermission("access.manage");
    $elementData = $this->getModelDataFor($type);
    if ($type == "datasource") {
      $model = Yii::$app->datasourceManager->create($namedId);
      $model->title = $namedId;
      $model->save();
      $this->dispatchClientMessage("reloadDatasources");
    } else {
      $modelClass = $elementData['class'];
      /** @var \lib\models\BaseModel $model */
      $model = new $modelClass([
        $elementData['labelProp'] => $namedId
      ]);
      $model->save();
    }
    return $this->actionEdit($type, $namedId);
  }

  /**
   * Edit the element data by returning a form to the user
   *
   * @param string|bool $first
   *    The type of the element or boolean true
   * @param string $second
   *    The namedId of the element
   * @param null|string $third
   *    If the first argument is boolean true, then the second and third
   *    arguments are the normal signature
   * @return array
   * @throws \JsonRpc2\Exception
   */
  public function actionEdit($first, $second, $third = null)
  {
    if ($first === true) {
      // if first argument is boolean true, this is the call from a dialog
      $type = $second;
      $namedId = $third;
    } else {
      // otherwise, normal call
      $type = $first;
      $namedId = $second;
    }

    if ($type != "user" or $namedId != $this->getActiveUser()->namedId) {
      $this->requirePermission("access.manage");
    }

    $model = $this->getModelInstance($type,$namedId);
    $formData = $this->createFormData($model);

    if ($type == "user") {
      $formData['password']['value'] = null;
      $formData['password2']['value'] = null;
    }

    $modelMap = $this->modelData();
    $message = "<h3>" . Yii::t('app', $modelMap[$type]['dialogLabel']) . " '" . $namedId . "'</h3>";

    return \lib\dialog\Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "saveFormData",
      array($type, $namedId)
    );
  }

  /**
   * Delete a model record
   * @param string $type
   *    The type of the model
   * @param array $ids
   *    An array of ids to delete
   * @throws UserErrorException
   *    Thrown if deleting one of the models is not allowed
   * @throws \JsonRpc2\Exception
   *    Thrown if the action is not allowed
   * @throws \Exception
   *    Thrown if model deletion encounters problems
   */
  public function actionDelete($type, array $ids)
  {
    $this->requirePermission("access.manage");
    $elementData = $this->getModelDataFor($type);
    $minId = null;
    switch ($type) {
      case "datasource":
        return \lib\dialog\Confirm::create(
          Yii::t('app', "Do you want to remove only the datasource entry or all associated data?"),
          [
            Yii::t('app', "All data"),
            Yii::t('app', "Entry only"), true
          ],
          Yii::$app->controller->id, "deleteDatasource", [$ids]
        );
      // @todo the $midId values should not be hardcoded, use a model column to protect records
      case "user":
        $minId = 2;
        break;

      case "permission":
        $minId = 29;
        break;

      case "role":
        $minId = 5;
        break;
    }

    foreach ((array)$ids as $namedId) {
      $modelClass = $elementData['class'];
      /** @var \lib\models\BaseModel $model */
      $model = $modelClass::findByNamedId($namedId);
      if (!$model) {
        throw new UserErrorException("Element $type/$namedId does not exists.");
      }
      if ($minId and $model->id < $minId) {
        throw new UserErrorException(
          Yii::t('app', "Deleting element '%s' of type '%s' is not allowed.", $namedId, $type)
        );
      }

      try {
        $model->delete();
      } catch (\Throwable $e) {
        throw new \Exception($e);
      }
      //$this->dispatchClientMessage("user.deleted", $user->id());
    }

    return "OK";
  }

  /**
   * Delete a datasource
   *
   * @param $doDeleteModelData
   * @param $namedId
   * @return string
   * @throws UserErrorException
   * @throws \JsonRpc2\Exception
   */
  public function actionDeleteDatasource($doDeleteModelData, $namedId)
  {
    if ($doDeleteModelData === null) {
      return "ABORTED";
    }
    $this->requirePermission("access.manage");

    try {
      Yii::$app->datasourceManager->delete($namedId, $doDeleteModelData);
      $this->broadcastClientMessage("accessControlTool.reloadLeftList");
    } catch (\Throwable $e) {
      Yii::error($e); // log it for inspection
      throw new UserErrorException(Yii::t('app', "Deleting datasource '{1}' failed... ", [$namedId]));
    }
    Alert::create(Yii::t('app', "Datasource '{1}' successfully deleted ... ", [$namedId]));
  }

  /**
   * Link two model records
   * @param string $treeElement
   *    A string consisting of type=namedId pairs, separated by commas, defining
   *    what models the tree elements connects
   * @param string $type
   *    The type of the current element
   * @param string $namedId
   *    The named id of the current element
   * @return string "OK"
   * @throws \JsonRpc2\Exception
   * @throws \InvalidArgumentException
   */
  public function actionLink($treeElement, $type, $namedId)
  {
    $this->requirePermission("access.manage");
    $this->linkOrUnlink( $treeElement, $type, $namedId, true);
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
  public function actionUnlink($treeElement, $type, $namedId)
  {
    $this->requirePermission("access.manage");
    $this->linkOrUnlink( $treeElement, $type, $namedId, false);
    return "OK";
  }

  /**
   * Save the form produced by editElement()
   * @param \stdClass|null $data
   *    The form data
   * @param string $type
   *    The type of the model
   * @param string $namedId
   *    The namedId of the model
   * @return string Message for debug purposes
   * @throws \Exception
   * @throws \JsonRpc2\Exception
   * @throws UserErrorException
   */
  public function actionSave( \stdClass $data, $type, $namedId)
  {
    if ($data === null) {
      return "Action save aborted";
    }

    $model = $this->getModelInstance($type, $namedId);
    $oldData = (object) $model->getAttributes();

    if ($type != "user" or $namedId != $this->getActiveUser()->namedId) {
      $this->requirePermission("access.manage");
      $this->dispatchClientMessage("accessControlTool.reloadLeftList");
    }

    // if we have a password field, we expect to have a password2 field
    // as well to match. return to dialog if passwords do not match.
    if (isset($data->password) and !empty($data->password)) {
      if (!isset($data->password2) or $data->password != $data->password2) {
         Alert::create(
          Yii::t('app', "Passwords do not match. Please try again"),
          Yii::$app->controller->id, "editElement", ["user", $namedId]
        );
        return "Passwords did not match";
      }
      unset( $data->password2 );
    }

    if ( $type == "user") {
      // Validate user data input
      if ($model->ldap ) {
        // ldap user data cannot be edited
        throw new UserErrorException(Yii::t('app', "User data is from an LDAP server and cannot be changed."));
      } else if( !$data->password and !$model->password ) {
        // enforce setting of password
        Alert::create(
          Yii::t('app', "You must set a password."),
          Yii::$app->controller->id, "handleMissingPasswordDialog", array($namedId)
        );
        return "Missing password";
      }
      //  @todo reimplement if password has changed, inform user, unless the old password was a temporary password
//      if ($data->password and $parsed->password != $oldData->password and strlen($oldData->password) > 7) {
//        return $this->sendInformationEmail($model->data());
//      }
    }

    // parse form and save in model
    try {
      $parsed = \lib\dialog\Form::parseResultData($model, $data);
      $model->setAttributes($parsed);
      $model->save();
    } catch ( UserErrorException $e) {
      // make user errors return to the edit method
      $message = $e->getMessage();
      Alert::create(
        $message,
        Yii::$app->controller->id, "editElement", array("user", $namedId)
      );
      return "User error '$message', reopening editor form.";
    }
    Alert::create(Yii::t('app', "The data has been saved."));
    return "OK";
  }

  /**
   * Presents the user with a form to enter user data
   *
   */
  public function actionNewUserDialog()
  {
    $message = Yii::t(
      'app',
      "Please enter the user data. A random password will be generated and sent to the user."
    );
    $formData = [
      'namedId' => [
        'label' => Yii::t('app', "Login name"),
        'type' => "textfield",
        'placeholder' => Yii::t('app', "Enter the short login name"),
        'validation' => [
          'required' => true,
          'validator' => "string"
        ]
      ],
      'name' => [
        'type' => "textfield",
        'label' => Yii::t('app', "Full name"),
        'placeholder' => Yii::t('app', "Enter the full name of the user"),
        'validation' => [
          'required' => true,
          'validator' => "string"
        ]
      ],
      'email' => [
        'type' => "textfield",
        'label' => Yii::t('app', "Email address"),
        'placeholder' => Yii::t('app', "Enter a valid Email address"),
        'validation' => [
          'required' => true,
          'validator' => "email"
        ]
      ],
    ];

    \lib\dialog\Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "add-user", array()
    );
    return "User form created.";
  }

  /**
   * Action to add a new user
   *
   * @param \stdClass $data
   * @return string
   * @throws \JsonRpc2\Exception
   * @throws UserErrorException
   * @throws \yii\base\Exception
   */
  public function actionAddUser( \stdClass $data)
  {
    $this->requirePermission("access.manage");

    if ($data === null) $this->cancelledActionResult();

    if( empty($data->namedId)) {
      throw new UserErrorException( Yii::t('app', "Missing login name"));
    }

    $user = $this->getModelInstance("user", $data->namedId );
    if( $user ){
      Alert::create(Yii::t(
        'app',
        "Login name '{1}' already exists. Please choose a different one.",
        [$data->namedId]
      ));
      return $this->abortedActionResult("User entered existing login name.");
    }

    /** @var \app\models\User $user */
    $userClass = get_class($user);
    $user = new $userClass;
    $user->setAttributes((array) $data );

    // give it the 'user' role
    /** @var \app\models\Role $roleClass */
    $roleClass = $this->getModelClassFor('role');
    $user->link( 'roles', $roleClass::findByNamedId("user") );

    // generate temporary password
    $tmpPasswd =Yii::$app->getSecurity()->generateRandomString(7);
    $user->password =  $tmpPasswd;
    $user->confirmed = true;  // @todo Remove when email confirmation is reimplemented
    $user->save();

    // @todo Reimplement: send confirmation link for new users
    //$data = (object) $user->getAttributes();
    //$this->sendConfirmationLinkEmail($data->email, $data->namedId, $data->name, $tmpPasswd);

    $this->dispatchClientMessage("accessControlTool.reloadLeftList");

    Alert::create( Yii::t(
      'app',
      "An email has been sent to {1} ({2}) with information on the registration.", [$data->name, $data->email])
    );
    return $this->successfulActionResult();
  }

  /**
   * Presents the user a form to enter the data of a new datasource to be created
   * @return string
   */
  public function actionNewDatasourceDialog()
  {
    $message = Yii::t('app', "Please enter the information on the new datasource.");
    $formData = array(
      'namedId' => array(
        'label' => Yii::t('app', "Name"),
        'type' => "textfield",
        'placeholder' => Yii::t('app', "The short name, e.g. researchgroup1"),
        'validation' => array(
          'required' => true,
          'validator' => "string"
        )
      ),
      'title' => array(
        'width' => 500,
        'type' => "textfield",
        'label' => Yii::t('app', "Title"),
        'placeholder' => Yii::t('app', "A descriptive title, e.g. Database of Research Group 1"),
        'validation' => array(
          'required' => true,
          'validator' => "string"
        )
      )
    );
    \lib\dialog\Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "add-datasource", array()
    );
    return $this->successfulActionResult();
  }

  /**
   * Action to add a new datasource from client-supplied data
   *
   * @param object $data
   * @return string
   * @throws \Exception
   * @throws \JsonRpc2\Exception
   * @throws \yii\db\Exception
   */
  public function actionAddDatasource($data)
  {
    $this->requirePermission("access.manage");

    if ($data === null) return $this->cancelledActionResult();

    try {
      Yii::$app->datasourceManager->create($data->namedId);
    } catch (\yii\db\Exception $e) {
      Alert::create(Yii::t(
        'app',
        "Datasource name '{1}' already exists. Please choose a different one.",
        [$data->namedId]
      ));
      return $this->abortedActionResult("Datasource exists.");
    }

    $this->dispatchClientMessage("accessControlTool.reloadLeftList");
    Alert::create( Yii::t(
      'app',
      "Datasource '%s' has been created. By default, it will not be visible to anyone. You have to link it with a group, a role, or a user first.",
      $data->namedId
    ));
    return $this->successfulActionResult();
  }

  /*
 ---------------------------------------------------------------------------
    HELPERS
 ---------------------------------------------------------------------------
  */

  /**
   * Function to check the match between the password and the repeated
   * password. Returns the hashed password.
   * @param $value
   * @throws \lib\exceptions\UserErrorException
   * @return string|null
   */
  public function checkFormPassword($value)
  {
    if (!isset($this->__password)) {
      $this->__password = $value;
    } elseif ($this->__password != $value) {
      throw new \lib\exceptions\UserErrorException(Yii::t('app',"Passwords do not match..."));
    }
    if ($value and strlen($value) < 8) {
      throw new \lib\exceptions\UserErrorException(Yii::t('app',"Password must be at least 8 characters long"));
    }
    return $value ? Yii::$app->accessManager->generateHash($value) : null;
  }

}
