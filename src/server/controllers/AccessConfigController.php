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
   * @return array
   *    An array with three elements: the first two elements are the main linked
   *    models, the third is the optional dependent model (for example, a group
   *    in a group-role context)
   */
  protected function getLinkedModels($treeElement, $type, $namedId)
  {
    $elementParts = explode(",", $treeElement);
    if (count($elementParts) > 1) {
      list($depModelType, $depModelId) = explode("=", $elementParts[0]);
      $depModel = $this->getModelInstance(trim($depModelType), trim($depModelId));
      $modelInfo = explode("=", $elementParts[1]);
    } else {
      $depModel = null;
      $modelInfo = explode("=", $elementParts[0]);
    }
    $model1 = $this->getModelInstance(trim($modelInfo[0]), trim($modelInfo[1]));
    $model2 = $this->getModelInstance($type, $namedId);
    return array($model1, $model2, $depModel);
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
        $description = $model->description;
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
   * @param $type
   * @param $ids
   */
  public function actionDelete($type, $ids)
  {
    $this->requirePermission("access.manage");
    $elementData = $this->getModelDataFor($type);
    $minId = null;
    switch ($type) {
      case "datasource":
        return \lib\dialog\Confirm::create(
          Yii::t('app', "Do you want to remove only the datasource entry or all associated data?"),
          array(Yii::t('app', "All data"), Yii::t('app', "Entry only"), true),
          Yii::$app->controller->id, "deleteDatasource", array($ids)
        );

      case "user":
        $minId = 2; // @todo should not be hardcoded
        break;

      case "permission":
        $minId = 29; // @todo should not be hardcoded
        break;

      case "role":
        $minId = 5; // @todo should not be hardcoded
        break;
    }

    foreach ((array)$ids as $namedId) {
      $modelClass = $elementData['class'];
      $model = $modelClass::findByNamedId($namedId);
      if (!$model) {
        throw new UserErrorException("Element $type/$namedId does not exists.");
      }
      if ($minId and $model->id() < $minId) {
        throw new UserErrorException(Yii::t('app', "Deleting element '%s' of type '%s' is not allowed.", $namedId, $type));
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
  public function actionDeleteDatasource($doDeleteModelData, $namedId)
  {
    if ($doDeleteModelData === null) {
      return "ABORTED";
    }
    $this->requirePermission("access.manage");

    try {
      Yii::$app->datasourceManager->delete($namedId, $doDeleteModelData);
      $this->broadcastClientMessage("accessControlTool.reloadLeftList");
    } catch (PDOException $e) {
      throw new UserErrorException(Yii::t('app', "Deleting datasource '{1}' failed... ", [$namedId]));
    }

    \lib\dialog\Alert::create(Yii::t('app', "Datasource '{1}' successfully deleted ... ", [$namedId]));
  }

  /**
   * Link two model records
   * @param $treeElement
   * @param $type
   * @param $namedId
   * @return string "OK"
   */
  public function actionLink($treeElement, $type, $namedId)
  {
    $this->requirePermission("access.manage");

    list($model1, $model2, $depModel) =
      $this->getLinkModels($treeElement, $type, $namedId);

    if ($depModel) {
      $model1->linkModel($model2, $depModel);
    } else {
      $model1->linkModel($model2);
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
  public function actionUnlin($treeElement, $type, $namedId)
  {
    $this->requirePermission("access.manage");

    list($model1, $model2, $depModel) =
      $this->getLinkModels($treeElement, $type, $namedId);

    if ($depModel) {
      $model1->unlinkModel($model2, $depModel);
    } else {
      $model1->unlinkModel($model2);
    }

    return "OK";
  }

  /**
   * Save the form produced by editElement()
   * @param $data
   * @param $type
   * @param $namedId
   * @throws \lib\exceptions\UserErrorException
   * @return \qcl_ui_dialog_Alert|string "OK"
   */
  public function actionSave($data, $type, $namedId)
  {

    if ($data === null) {
      return "ABORTED";
    }

    if ($type != "user" or $namedId != $this->getActiveUser()->namedId) {
      $this->requirePermission("access.manage");
      $this->dispatchClientMessage("accessControlTool.reloadLeftList");
    }

    /*
     * if we have a password field, we expect to have a password2 field
     * as well to match. return to dialog if passwords do not match.
     */
    if (isset($data->password) and !empty($data->password)) {
      if (!isset($data->password2) or $data->password != $data->password2) {
        return \lib\dialog\Alert::create(
          Yii::t('app', "Passwords do not match. Please try again"),
          Yii::$app->controller->id, "editElement", array("user", $namedId)
        );
      }
    }

    $model = $this->getElementModel($type);
    $model->load($namedId);

    /*
     * no ldap user data
     */
    if ($type == "user" and $model->get("ldap")) {
      throw new \Exception(Yii::t('app', "User data is from an LDAP server and cannot be changed."));
    }

    try {
      $parsed = (object)$this->parseFormData($model, $data);
    } catch (\lib\exceptions\UserErrorException $e) {
      return \lib\dialog\Alert::create(
        $e->getMessage(),
        Yii::$app->controller->id, "editElement", array("user", $namedId)
      );
    }

    /*
     * set data
     */
    $oldData = (object)$model->data();
    $model->set($parsed)->save();

    /*
     * user model
     */
    if ($type == "user" and !$model->get("ldap")) {
      /*
       * enforce setting of password
       */
      if (!$data->password and !$model->getPassword()) {
        return \lib\dialog\Alert::create(
          Yii::t('app', "You must set a password."),
          Yii::$app->controller->id, "handleMissingPasswordDialog", array($namedId)
        );
      }

      /*
       * if password has changed, inform user, unless the old password was a
       * temporary pasword
       */
      if ($data->password and $parsed->password != $oldData->password and strlen($oldData->password) > 7) {
        return $this->sendInformationEmail($model->data());
      }
    }
    return \lib\dialog\Alert::create(Yii::t('app', "The data has been saved."));
  }

  /**
   * Presents the user with a form to enter user data
   */
  public function actionNewUserDialog()
  {
    $message = Yii::t('app', "Please enter the user data. A random password will be generated and sent to the user.");
    $formData = array(
      'namedId' => array(
        'label' => Yii::t('app', "Login name"),
        'type' => "textfield",
        'placeholder' => Yii::t('app', "Enter the short login name"),
        'validation' => array(
          'required' => true,
          'validator' => "string"
        )
      ),
      'name' => array(
        'type' => "textfield",
        'label' => Yii::t('app', "Full name"),
        'placeholder' => Yii::t('app', "Enter the full name of the user"),
        'validation' => array(
          'required' => true,
          'validator' => "string"
        )
      ),
      'email' => array(
        'type' => "textfield",
        'label' => Yii::t('app', "Email address"),
        'placeholder' => Yii::t('app', "Enter a valid Email address"),
        'validation' => array(
          'required' => true,
          'validator' => "email"
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
  public function actionAddUser($data)
  {
    $this->requirePermission("access.manage");

    if ($data === null) return "CANCEL";

    qcl_assert_valid_string($data->namedId, "Invalid login name");

    $model = $this->getElementModel("user");

    try {
      $model->create($data->namedId);
      unset($data->namedId);
    } catch (qcl_data_model_RecordExistsException $e) {
      return \lib\dialog\Alert::create(Yii::t('app', "Login name '%s' already exists. Please choose a different one.", $data->namedId));
    }

    $model->set($data)->save();

    // make it a normal user
    $this->getElementModel("role")->load(QCL_ROLE_USER)->linkModel($model);

    // generate temporary password
    $tmpPasswd = qcl_generate_password(7);
    $model->set("password", $tmpPasswd)->save();

    $data = (object)$model->data();
    $this->sendConfirmationLinkEmail($data->email, $data->namedId, $data->name, $tmpPasswd);

    $this->dispatchClientMessage("accessControlTool.reloadLeftList");

    return \lib\dialog\Alert::create(
      Yii::t('app', "An email has been sent to %s (%s) with information on the registration.", $data->name, $data->email)
    );

  }

  /**
   * Presents the user a form to enter the data of a new datasource to be created
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
  public function actionAddDatasource($data)
  {
    $this->requirePermission("access.manage");

    if ($data === null) return "CANCEL";
    $model = $this->getElementModel("datasource");

    try {
      $this->getApplication()->createDatasource($data->namedId, array('title' => $data->title));
    } catch (qcl_data_model_RecordExistsException $e) {
      return \lib\dialog\Alert::create(Yii::t('app', "Datasource name '%s' already exists. Please choose a different one.", $data->namedId));
    }

    $this->dispatchClientMessage("accessControlTool.reloadLeftList");
    return \lib\dialog\Alert::create(
      Yii::t('app',
        "Datasource '%s' has been created. By default, it will not be visible to anyone. You have to link it with a group, a role, or a user first.",
        $data->namedId
      )
    );
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
    return $value ? Yii::$app->getAccessController()->generateHash($value) : null;
  }

}
