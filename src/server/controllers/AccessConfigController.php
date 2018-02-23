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
use lib\dialog\{
  Alert, Confirm, Error, Form
};
use lib\exceptions\{
  RecordExistsException, UserErrorException
};
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
   * @throws UserErrorException
   * @todo can this go into AppController?
   */
  protected function getModelInstance($type, $namedId)
  {
    $modelClass = $this->getModelDataFor($type)['class'];
    $model = $modelClass::findByNamedId($namedId);
    if ($model) {
      return $model;
    }
    throw new UserErrorException(
      Yii::t(
        'app',
        "An object of type {type} and id {namedId} does not exist.",
        ['type' => $type, 'namedId' => $namedId]
      )
    );
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
   * @param string $linkedModelData
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
   * @throws UserErrorException
   */
  protected function linkOrUnlink($linkedModelData, $type, $namedId, $link = true)
  {
    $elementParts = explode(",", $linkedModelData);
    $extraColumns = [];
    if (count($elementParts) > 1) {
      // we have a dependent model
      list($depModelType, $depModelNamedId) = explode("=", $elementParts[0]);
      $linkedModelInfo = explode("=", $elementParts[1]);
      /** @var \lib\models\BaseModel $depModel */
      $depModel = $this->getModelInstance(trim($depModelType), trim($depModelNamedId));
      if ($depModelType != "group") {
        throw new \InvalidArgumentException("Invalid dependent model type '$depModelType'");
      }
      $extraColumns = ['GroupId' => $depModel->id];
    } else {
      $depModelArray = null;
      $linkedModelInfo = explode("=", $elementParts[0]);
    }
    $model = $this->getModelInstance($type, $namedId);
    $linkedModelType = trim($linkedModelInfo[0]);
    $linkedModelRelation = $linkedModelType . "s";
    $linkedModelNamedId = trim($linkedModelInfo[1]);
    $linkedModel = $this->getModelInstance($linkedModelType, $linkedModelNamedId);
    Yii::debug(
      "Linking $type '$namedId' with $linkedModelType '$linkedModelNamedId' via '$linkedModelRelation' relation" .
      ($extraColumns ? " with extra columns " . \json_encode($extraColumns) : ".")
    );
    if ($link) {
      try {
        $model->link($linkedModelRelation, $linkedModel, $extraColumns);
      } catch (\Exception $e) {
        if( $e instanceof yii\base\InvalidCallException or $e instanceof \PDOException) {
          throw new UserErrorException("Models are already linked");
        }
      }
    } else {
      $model->unlink($linkedModelRelation, $linkedModel, $extraColumns);
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
   * @throws UserErrorException
   * @throws \JsonRpc2\Exception
   */
  public function actionElements($type, array $filter = null)
  {
    $this->requirePermission("access.manage");
    $activeUser = $this->getActiveUser();
    $isAdmin = $activeUser->hasRole("admin");
    // query
    try {
      $elementData = $this->getModelDataFor($type);
    } catch (\InvalidArgumentException $e) {
      throw new UserErrorException($e->getMessage());
    }
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
   * Returns the data of the given model (identified by type and id)
   * Only for testing, disabled in production
   * @param $type
   * @param $namdeId
   * @throws \JsonRpc2\Exception
   * @throws UserErrorException
   */
  public function actionData($type, $namedId)
  {
    $this->requirePermission("access.manage");
    if (YII_ENV_PROD) {
      throw new \JsonRpc2\Exception("Not allowed.", \JsonRpc2\Exception::INVALID_REQUEST);
    }
    $model = $this->getModelInstance($type, $namedId);
    return $model->getAttributes(null, ['created', 'modified']);
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
      'icon' => "icon/16/apps/utilities-network-manager.png",
      'label' => Yii::t('app', "Relations"),
      'action' => null,
      'value' => null,
      'type' => null,
      'children' => [],
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
        'icon' => $linkedElementdata['icon'],
        'label' => $linkedElementdata['label'],
        'type' => $linkedType,
        'action' => "link",
        'value' => $elementType . "=" . $namedId,
        'children' => []
      );

      // user -> roles
      if ($elementType == "user" and $linkedType == "role") {
        /** @var \app\models\User $user */
        $user = $model;
        // you cannot link to this node
        $node['action'] = null;
        $node['value'] = null;

        // pseudo group node -> no group dependency
        $groupNode = [
          'icon' => $modelData['group']['icon'],
          'label' => Yii::t('app', "In all groups"),
          'type' => "group",
          'action' => "link",
          'value' => "user=" . $user->namedId,
          'children' => []
        ];
        $roles = $user->getGroupRoles(null)->all();
        foreach ($roles as $role) {
          $roleNode = [
            'icon' => $modelData['role']['icon'],
            'label' => $role->name,
            'type' => "role",
            'action' => "unlink",
            'value' => "role=" . $role->namedId,
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
            'icon' => $modelData['group']['icon'],
            'label' => Yii::t('app', "In {group}", [
              'group' => $group->name
            ]),
            'type' => "group",
            'action' => "link",
            'value' => "group=" . $group->namedId . ",user=" . $user->namedId,
            'children' => []
          );
          /** @var \app\models\Role[] $roles */
          $roles = $user->getGroupRoles($group->id)->all();
          foreach ($roles as $role) {
            $roleNode = array(
              'icon' => $modelData['role']['icon'],
              'label' => $role->name,
              'type' => "role",
              'action' => "unlink",
              'value' => "group=" . $group->namedId . ",role=" . $role->namedId,
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
          $labelAttribute = $modelData[$linkedType]['labelProp'];
          $linkedNode = [
            'icon' => $modelData[$linkedType]['icon'],
            'label' => $linkedModel->getAttribute($labelAttribute),
            'type' => $linkedType,
            'action' => "unlink",
            'value' => "$linkedType=" . $linkedModel->namedId,
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
   * @param bool $edit
   * @return string
   * @throws \JsonRpc2\Exception
   * @throws UserErrorException
   * @todo Implement support for other datasource types
   */
  public function actionAdd($type, $namedId, $edit = true)
  {
    $this->requirePermission("access.manage");
    $elementData = $this->getModelDataFor($type);
    $duplicateMessage = Yii::t(
      'app',
      "A {type} named '{name}' already exists. Please pick another name.",
      ['type' => $type, 'name' => $namedId]
    );
    $errorMessage = Yii::t(
      'app',
      "Error saving {type} named '{name}':",
      ['type' => $type, 'name' => $namedId]
    );

    if ($type == "datasource") {
      try {
        $model = Yii::$app->datasourceManager->create($namedId);
      } catch (RecordExistsException $e) {
        throw new UserErrorException($duplicateMessage);
      } catch (\Exception $e) {
        throw new UserErrorException($e->getMessage());
      }
      $model->title = $namedId;
      try {
        $model->save();
      } catch (\yii\db\Exception $e) {
        throw new UserErrorException($errorMessage . $e->getMessage());
      }
      $this->dispatchClientMessage("reloadDatasources");
    } else {
      $modelClass = $elementData['class'];
      if ($modelClass::findByNamedId($namedId)) {
        throw new UserErrorException($duplicateMessage);
      }
      /** @var \lib\models\BaseModel $model */
      $model = new $modelClass([
        'namedId' => $namedId,
        $elementData['labelProp'] => $namedId
      ]);
      try {
        $model->save();
      } catch (\yii\db\Exception $e) {
        throw new UserErrorException($errorMessage . $e->getMessage());
      }
    }
    if ($edit) {
      $this->actionEdit($type, $namedId);
      return "Created record and form for $type $namedId.";
    }
    return "Created record for $type $namedId.";
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
   * @return string
   * @throws \JsonRpc2\Exception
   * @throws UserErrorException
   */
  public function actionEdit($first, $second, $third = null)
  {
    // if first argument is boolean true, this is the call from a dialog
    if ($first === true) {
      // if the second argument is a shelf id, get arguments from shelf
      if ($this->hasInShelf($second)) {
        list($type, $namedId) = $this->unshelve($second);
      } else {
        $type = $second;
        $namedId = $third;
      }
    } else {
      // otherwise, normal call
      $type = $first;
      $namedId = $second;
    }

    if ($type != "user" or $namedId != $this->getActiveUser()->namedId) {
      $this->requirePermission("access.manage");
    }

    // create form
    $model = $this->getModelInstance($type, $namedId);
    try {
      $formData = Form::getDataFromModel($model);
    } catch (\Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    if ($type == "user") {
      $formData['password']['value'] = null;
      $formData['password2']['value'] = null;
    }
    $label = $this->getModelDataFor($type)['dialogLabel'];
    $message = "<h3>$label '$namedId'</h3>";
    Form::create(
      $message, $formData, true,
      Yii::$app->controller->id, "save", [$type, $namedId]
    );
    return "Created form for $type $namedId";
  }


  /**
   * Save the form produced by edit()
   * @param \stdClass|null $data
   *    The form data or null if the user cancelled the form
   * @param string|null $type
   *    The type of the model or null if the user cancelled the form
   * @param string|null $namedId
   *    The namedId of the model or null if the user cancelled the form
   * @return string Diagnostic message
   * @throws \Exception
   * @throws \JsonRpc2\Exception
   * @throws UserErrorException
   */
  public function actionSave(\stdClass $data = null, $type = null, $namedId = null)
  {
    if ($data === null) {
      return "Action save aborted";
    }

    $model = $this->getModelInstance($type, $namedId);
    $oldData = (object)$model->getAttributes();
    $validationError = null;
    $passwordChanged = false;

    // lower permission requirement if users edit their own data
    if ($type != "user" or $namedId != $this->getActiveUser()->namedId) {
      $this->requirePermission("access.manage");
    }

    // password handling
    if (isset($data->password)) {
      if (!empty($data->password) and isset($data->password2)) {
        // if we have a password2 field, we expect it to match.
        if ($data->password !== $data->password2) {
          $validationError = Yii::t('app', "Passwords did not match.");
          unset($data->password);
        }
        unset($data->password2);
      }
      // enforce minimal password length
      if (isset($data->password) and strlen($data->password) < 8) {
        $validationError = Yii::t('app', "Password must be at least 8 characters long.");
        unset($data->password);
      }
      // we have a valid password
      if (isset($data->password)) {
        $hashed = Yii::$app->accessManager->generateHash($data->password);
        Yii::debug("new: $data->password/$hashed, old: $model->password ");
        $data->password = $hashed;
        $passwordChanged = true;
      }
    }

    // ldap user data cannot be edited
    if ($type == "user" and $model->ldap) {
      throw new UserErrorException(Yii::t('app', "User data is from an LDAP server and cannot be changed."));
    }

    // parse form and save in model
    try {
      $parsed = Form::parseResultData($model, $data);
      $model->setAttributes($parsed);
      $model->save();
    } catch (\Exception $e) {
      // make user errors return to the edit method
      $message = $e->getMessage();
      $shelfId = $this->shelve($data, $type, $namedId);
      Alert::create(
        $message,
        Yii::$app->controller->id, "edit", [$shelfId]
      );
      return "User error '$message', reopening editor form.";
    }

    // enforce setting of password
    if ($model->hasAttribute("password") and !$model->password) {
      $validationError = Yii::t('app', "You need to set a password.");
    }

    if ($passwordChanged) {
      //  @todo reimplement if password has changed, inform user, unless the old password was a temporary password
      if (false and strlen($oldData->password) > 7) {
        return $this->sendInformationEmail($model->data());
      }
      Alert::create(Yii::t('app', "Your password has been changed."));
    }

    if ($validationError) {
      $shelfId = $this->shelve($type, $namedId);
      Error::create(
        $validationError,
        Yii::$app->controller->id, "edit", [$shelfId]
      );
      return "Data validation error: $validationError";
    } else {
      // message to update the UI
      $this->dispatchClientMessage("accessControlTool.reloadLeftList");
      return "Data for $type '$namedId' has been saved";
    }
  }

  /**
   * Delete a model record
   * @param string $type
   *    The type of the model
   * @param $ids
   *    An array of ids to delete
   * @return string Diagnostic message
   * @throws UserErrorException
   * @throws \JsonRpc2\Exception
   * @throws \Exception
   *
   */
  public function actionDelete($type, $ids)
  {
    $this->requirePermission("access.manage");
    $elementData = $this->getModelDataFor($type);
    $ids = (array)$ids;
    $minId = null;
    switch ($type) {
      case "datasource":
        return Confirm::create(
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
        $minId = 27;
        break;
      case "role":
        $minId = 5;
        break;
    }

    foreach ($ids as $namedId) {
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

    return "Created delete confirmation dialog";
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
   * @param string $linkedModelData
   *    A string consisting of type=namedId pairs, separated by commas, defining
   *    what models should be linked to the main model
   * @param string $type
   *    The type of the current element
   * @param string $namedId
   *    The named id of the current element
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   * @throws \InvalidArgumentException
   * @throws UserErrorException
   */
  public function actionLink($linkedModelData, $type, $namedId)
  {
    $this->requirePermission("access.manage");
    $this->linkOrUnlink($linkedModelData, $type, $namedId, true);
    return "Linked $type '$namedId' with $linkedModelData";
  }

  /**
   * Unlink two model records
   *
   * @param $linkedModelData
   * @param $type
   * @param $namedId
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   * @throws UserErrorException
   */
  public function actionUnlink($linkedModelData, $type, $namedId)
  {
    $this->requirePermission("access.manage");
    $this->linkOrUnlink($linkedModelData, $type, $namedId, false);
    return "Unlinked $type '$namedId' from $linkedModelData";
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

    Form::create(
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
  public function actionAddUser(\stdClass $data)
  {
    $this->requirePermission("access.manage");

    if ($data === null) $this->cancelledActionResult();

    if (empty($data->namedId)) {
      throw new UserErrorException(Yii::t('app', "Missing login name"));
    }

    $user = $this->getModelInstance("user", $data->namedId);
    if ($user) {
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
    $user->setAttributes((array)$data);

    // give it the 'user' role
    /** @var \app\models\Role $roleClass */
    $roleClass = $this->getModelClassFor('role');
    $user->link('roles', $roleClass::findByNamedId("user"));

    // generate temporary password
    $tmpPasswd = Yii::$app->getSecurity()->generateRandomString(7);
    $user->password = $tmpPasswd;
    $user->confirmed = true;  // @todo Remove when email confirmation is reimplemented
    $user->save();

    // @todo Reimplement: send confirmation link for new users
    //$data = (object) $user->getAttributes();
    //$this->sendConfirmationLinkEmail($data->email, $data->namedId, $data->name, $tmpPasswd);

    $this->dispatchClientMessage("accessControlTool.reloadLeftList");

//    Alert::create(Yii::t(
//      'app',
//      "An email has been sent to {1} ({2}) with information on the registration.", [$data->name, $data->email])
//    );
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
    Form::create(
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
    Alert::create(Yii::t(
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
      throw new \lib\exceptions\UserErrorException(Yii::t('app', "Passwords do not match..."));
    }
    if ($value and strlen($value) < 8) {
      throw new \lib\exceptions\UserErrorException(Yii::t('app', "Password must be at least 8 characters long"));
    }
    return $value ? Yii::$app->accessManager->generateHash($value) : null;
  }

}
