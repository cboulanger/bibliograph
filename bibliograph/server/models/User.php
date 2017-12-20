<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2017 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\models;

use app\models\Group;
use app\models\Permissions;
use app\models\Roles;
use app\models\User_Role;
use Yii;

/**
 * This is the model class for table "data_User".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $name
 * @property string $password
 * @property string $email
 * @property integer $anonymous
 * @property integer $ldap
 * @property integer $active
 * @property string $lastAction
 * @property integer $confirmed
 * @property integer $online
 */
class User extends app\models\BaseModel
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_User';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified', 'lastAction'], 'safe'],
      [['anonymous', 'ldap', 'active', 'confirmed', 'online'], 'integer'],
      [['namedId', 'password'], 'string', 'max' => 50],
      [['name'], 'string', 'max' => 100],
      [['email'], 'string', 'max' => 255],
      [['namedId'], 'unique'],
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'id' => Yii::t('app', 'ID'),
      'namedId' => Yii::t('app', 'Named ID'),
      'created' => Yii::t('app', 'Created'),
      'modified' => Yii::t('app', 'Modified'),
      'name' => Yii::t('app', 'Name'),
      'password' => Yii::t('app', 'Password'),
      'email' => Yii::t('app', 'Email'),
      'anonymous' => Yii::t('app', 'Anonymous'),
      'ldap' => Yii::t('app', 'Ldap'),
      'active' => Yii::t('app', 'Active'),
      'lastAction' => Yii::t('app', 'Last Action'),
      'confirmed' => Yii::t('app', 'Confirmed'),
      'online' => Yii::t('app', 'Online'),
    ];
  }

  public function getFormData()
  {
    return [
      'name' => array(
        'name' => "name",
        'label' => $this->tr("Full name"),
      ),
      'email' => array(
        'label' => $this->tr("Email address"),
        'placeholder' => $this->tr("Enter a valid Email address"),
        'validation' => array(
          'validator' => "email",
        ),
      ),
      'password' => array(
        'label' => $this->tr("Password"),
        'type' => "PasswordField",
        'value' => "",
        'placeholder' => $this->tr("To change the password, enter new password."),
        'marshaler' => array(
          'unmarshal' => array('callback' => array("this", "checkFormPassword")),
        ),
      ),
      'password2' => array(
        'label' => $this->tr("Repeat password"),
        'type' => "PasswordField",
        'value' => "",
        'ignore' => true,
        'placeholder' => $this->tr("To change the password, repeat new password"),
        'marshaler' => array(
          'unmarshal' => array('callback' => array("this", "checkFormPassword")),
        ),
      ),
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  protected function getUserRoles()
  {
    return $this->hasMany(User_Role::className(), ['UserId' => 'id']);
  }

  protected function getRoles()
  {
    return $this->hasMany(Role::className(), ['id' => 'RoleId'])->via('userRoles');
  }

  protected function getUserGroups()
  {
    return $this->hasMany(Group_User::className(), ['UserId' => 'id']);
  }

  protected function getGroups()
  {
    return $this->hasMany(Group::className(), ['id' => 'GroupId'])->via('userGroups');
  }  

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Alias of #namedId
   * @return string
   */
  public function getUsername()
  {
    return $this->namedId;
  }

  /**
   * Whether the given user name is the name of a guest (anonymous) user
   * @return bool True if user name is guest
   * @todo we need some more sophisticated stuff here
   */
  public function isAnonymous()
  {
    return (bool) $this->getAnonymous();
  }

  /**
   * Returns the value of the "online" property. This doesn't guarantee that the
   * value actually reflects the user's online status - it is the role of the access
   * controller to set/unset it.
   * @return boolean
   */
  public function isOnline()
  {
    return $this->online;
  }

  /**
   * Checks if the current user has the given permission
   * respects wildcards, i.e. myapp.permissions.* covers
   * myapp.permissions.canDoFoo
   * @param string $requestedPermission the permission to check
   * @return bool
   * @todo cache result for performance
   */
  public function hasPermission($requestedPermission)
  {
    static $cache = array();
    if (isset($cache[$requestedPermission])) {
      return $cache[$requestedPermission];
    } else {
      $hasPermission = $this->_hasPermission($requestedPermission);
      $cache[$requestedPermission] = $hasPermission;
      return $hasPermission;
    }
  }

  /**
   * The implementation of hasPermission
   * @param $requestedPermission
   * @return bool
   */
  protected function _hasPermission($requestedPermission)
  {

    // get all permissions of the user
    $permissions = $this->permissions();

    // use wildcard?
    $useWildcard = strstr($requestedPermission, "*");

    // check if permission is granted
    foreach ($permissions as $permission) {
      /*
       * exact match
       */
      if ($permission == $requestedPermission) {
        return true;
      } /*
       * else if the current permission name contains a wildcard
       */
      elseif (($pos = strpos($permission, "*")) !== false) {
        if (substr($permission, 0, $pos) == substr($requestedPermission, 0, $pos)) {
          return true;
        }
      } /*
       * else if the requested permission contains a wildcard
       */
      elseif ($useWildcard and ($pos = strpos($requestedPermission, "*")) !== false) {
        if (substr($permission, 0, $pos) == substr($requestedPermission, 0, $pos)) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Whether the user has the given role
   * @param string $role
   * @return bool
   * @todo this can be optimized
   */
  public function hasRole($role)
  {
    return in_array($role, $this->roles());
  }

  /**
   * Returns list of roles that a user has.
   * @param bool $refresh
   *    If true, reload group memberships. If false(default),
   *    use cached values
   * @return string[]
   *    Array of role names
   */
  public function roles($refresh = false)
  {
    if ($refresh or !$this->roles) {
      $roles = array();

      /*
       * simple user-role link
       * FIXME rewrite this, now group-specific roles ar NOT ignored
       */
      if (Yii::$app->utils->getIniValue("access.global_roles_only")) {
        try {
          $roleModel->findLinked($this);
          while ($roleModel->loadNext()) {
            $roles[] = $roleModel->namedId();
          }
        } catch (qcl_data_model_RecordNotFoundException $e) {
        }
      } /*
       * users have roles dependent on group
       */
      else {
        $groups = $this->groups();
        $groupModel = $this->getGroupModel();

        /*
         * get the group-dependent role
         */
        foreach ($groups as $groupName) {
          $groupModel->load($groupName);
          try {
            $roleModel->findLinked($this, $groupModel);
            while ($roleModel->loadNext()) {
              $roles[] = $roleModel->namedId();
            }
          } catch (qcl_data_model_RecordNotFoundException $e) {
          }
        }

        /*
         * add the global roles
         */
        try {
          $roleModel->findLinkedNotDepends($this, $groupModel);
          while ($roleModel->loadNext()) {
            $roles[] = $roleModel->namedId();
          }
        } catch (qcl_data_model_RecordNotFoundException $e) {
        }
      }
      $this->roles = array_unique($roles);
    }
    return $this->roles;
  }

  /**
   * Returns list of groups that a user belongs to.
   *
   * @param bool $refresh
   *    If true, reload group memberships. If false(default),
   *    use cached values
   *
   * @return array
   *    Array of string values: group named ids.
   */
  public function groups($refresh = false)
  {
    $groupModel = $this->getGroupModel();
    if ($refresh or !$this->groups) {
      $groups = array();
      try {
        $groupModel->findLinked($this);
        while ($groupModel->loadNext()) {
          $groups[] = $groupModel->namedId();
        }
      } catch (qcl_data_model_RecordNotFoundException $e) {
      }
      $this->groups = $groups;
    }
    return $this->groups;
  }

  /**
   * Returns list of permissions that the user has
   *
   * @param bool $refresh
   *    If true, reload group memberships. If false(default),
   *    use cached values
   * @return string[]
   *    Array of permission ids
   */
  public function permissions($refresh = false)
  {
    if ($refresh or !$this->permissions) {
      $roleModel = $this->getRoleModel();
      $roles = $this->roles($refresh);
      $permissions = array();
      foreach ($roles as $roleName) {
        $roleModel->load($roleName);
        $permissions = array_merge(
          $permissions,
          $roleModel->permissions()
        );
      }
      $this->permissions = $permissions;
    }
    return $this->permissions;
  }

  /**
   * Overridden to clear cached roles and permissions
   * @see class/qcl/data/model/qcl_data_model_AbstractNamedActiveRecord#load()
   */
  public function load($id)
  {
    $this->roles = null;
    $this->permissions = null;
    $this->groups = null;
    return parent::load($id);
  }

  /**
   * Resets the timestamp of the last action  for the current user
   * @return void
   */
  public function resetLastAction()
  {
    $this->set("lastAction", new qcl_data_db_Timestamp("now"));
    $this->save();
  }

  /**
   * Returns number of seconds since resetLastAction() has been called
   * for the current user
   * @return int seconds
   */
  public function getSecondsSinceLastAction()
  {
    $now = new qcl_data_db_Timestamp();
    $lastAction = $this->get("lastAction");
    if ($lastAction) {
      $d = $now->diff($lastAction);
      return (int) ($d->s + (60 * $d->i) + (3600 * $d->h) + 3600 * 24 * $d->d);
    }
    return 0;
  }

  /**
   * Function to check the match between the password and the repeated
   * password. Returns the hashed password.
   * @param $value
   * @throws JsonRpcException
   * @return string|null
   */
  public function checkFormPassword($value)
  {
    if (!isset($this->__password)) {
      $this->__password = $value;
    } elseif ($this->__password != $value) {
      throw new JsonRpcException($this->tr("Passwords do not match..."));
    }
    if ($value and strlen($value) < 8) {
      throw new JsonRpcException($this->tr("Password must be at least 8 characters long"));
    }
    return $value ? $this->getApplication()->getAccessController()->generateHash($value) : null;
  }

  /**
   * Overridden. Checks if user is anonymous and inactive, and deletes user if so.
   * @see qcl_data_model_AbstractActiveRecord::checkExpiration()
   * @todo Unhardcode expiration time
   */
  protected function checkExpiration()
  {
    $purge = ($this->isAnonymous() && $this->getSecondsSinceLastAction() > 600);
    if ($purge) {
      $this->delete();
    }
    return false;
  }

  /**
   * Overridden to dispatch a message "user.deleted" with the user id when a
   * user is deleted
   * @see qcl_data_model_AbstractActiveRecord::delete()
   */
  public function delete()
  {
    $this->dispatchMessage("user.deleted", $this->id());
    parent::delete();
  }
}
