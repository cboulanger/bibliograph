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

use Yii;

use app\models\BaseModel;
use app\models\Group;
use app\models\Permissions;
use app\models\Roles;
use app\models\UserConfig;
use app\models\Session;
use app\models\User_Role;


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
class User extends BaseModel
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

  /**
   * @return \yii\db\ActiveQuery
   */         
  protected function getUserRoles()
  {
    return $this->hasMany(User_Role::className(), ['UserId' => 'id'] );
  }

  /**
   * @return \yii\db\ActiveQuery
   */       
  public function getGroupRoles($groupId=null)
  {
    return $this->hasMany(Role::className(), ['id' => 'RoleId' ])
      ->via('userRoles', function( yii\db\ActiveQuery $query) use($groupId) {
        return $query->andWhere(['groupId'=>$groupId]);
      });
  }

  /**
   * @return \yii\db\ActiveQuery
   */      
  protected function getUserGroups()
  {
    $userGroups = $this->hasMany(Group_User::className(), ['UserId' => 'id']);
    return $userGroups;
  }

  /**
   * @return \yii\db\ActiveQuery
   */      
  public function getGroups()
  {
    return $this->hasMany(Group::className(), ['id' => 'GroupId'])
      ->via('userGroups');
  } 

  /**
   * @return \yii\db\ActiveQuery
   */    
  protected function getUserConfigs()
  {
    return $this->hasMany(UserConfig::className(), ['UserId' => 'id']);
  } 

  /**
   * @return \yii\db\ActiveQuery
   */  
  public function getSessions()
  {
    return $this->hasMany(Session::className(), ['UserId' => 'id']);
  } 

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getUserDatasources()
  {
    return $this->hasMany(Datasource_User::className(), ['UserId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  public function getDatasources()
  {
    return $this->hasMany(Datasource::className(), ['id' => 'DatasourceId'])
      ->via('userDatasources');
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
    return (bool) $this->anonymous;
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
    static $permissions = null;

    // get all permissions of the user
    if( is_null($permission) ) $permissions = $this->getPermissionNames();

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
   * Whether the user has the given role (in a group, if given)
   * @param string $role
   * @param int $groupId
   * @return bool
   */
  public function hasRole($role, $groupId = null)
  {
    return in_array($role, $this->getRoleNames( $groupId ));
  }

  /**
   * Returns list of roles that a user has, either globally or in a group.
   * @param int|null $groupId
   *    If given, retrieve only the roles that the user has in the
   *    group with the given id. Otherwise, return global roles only
   * @return string[]
   *    Array of role names
   */
  public function getRoleNames( $groupId = null )
  {
    $roleNames = [];
    $roleObjects = $this->getGroupRoles( $groupId )->all();
    foreach( $roleObjects as $obj) $roleNames[] = $obj->namedId;
    // if in group, add global roles
    if ( $groupId ){
      array_merge( $roleNames, $this->getRoleNames() );
    }
    return array_unique($roleNames);
  }

  /**
   * Returns list of groups that a user belongs to.
   *
   * @return string[]
   *    Array of string values: group named ids.
   */
  public function getGroupNames($refresh = false)
  {
    $result = $this->getGroups()->all();
    if( is_null( $result ) ) return [];
    return array_map( function($o) {return $o->namedId;}, $result );
  }

  /**
   * Returns list of permissions that the user has
   * @param int|null $groupId
   *    If given, retrieve only the permissions that the user has in the
   *    group with the given id. Otherwise, return global roles only
   * @return string[]
   *    Array of permission names
   */
  public function getPermissionNames( $groupId = null )
  {
    $permissionNames = [];
    $roles = $this->getGroupRoles( $groupId )->all();
    if( is_array($roles) ) foreach( $roles as $obj) {
      $permissionNames = array_merge( $permissionNames, $obj->getPermissionNames() );
    }
    // if in group, add global roles
    if ( $groupId ){
      $permissionNames = array_merge( $permissionNames, $this->getPermissionNames() );
    }
    return array_unique($permissionNames);
  }

  /**
   * Returns list of datasources that the user has access to 
   * @return string[]
   *    Array of datasource names
   */
  public function getDatasourceNames()
  {
    $datasourceNames = [];
    $myDatasources = $this->getDatasources()->all();
    if( is_array($myDatasources) ) foreach( $myDatasources as $o ) $datasourceNames[] = $o->namedId;
    $groups = $this->getGroups()->all();
    if( is_array($groups) ) foreach( $groups as $group){
      $datasourceNames = array_merge( $datasourceNames, $group->getDatasourceNames());
      $roles = $this->getGroupRoles( $group->id )->all();
      if( is_array($roles) ) foreach( $roles as $role) {
          $datasourceNames = array_merge( $datasourceNames, $role->getDatasourceNames() );
      }
    }
    return array_unique($datasourceNames);
  }  

  /**
   * Resets the timestamp of the last action  for the current user
   * @return void
   */
  public function resetLastAction()
  {
    not_implemented();
  }
}
