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

use app\migrations\data\m180105_075537_data_RoleDataInsert;
use function GuzzleHttp\describe_type;
use InvalidArgumentException;
use Yii;
use Yii\db\ActiveQuery;
use yii\db\Expression;
use yii\web\IdentityInterface;
use lib\models\BaseModel;


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
 * @property string $token
 * @property ActiveQuery $groups
 * @property ActiveQuery $roles
 * @property ActiveQuery $sessions
 * @property ActiveQuery $datasources
 */
class User extends BaseModel implements IdentityInterface
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
      [['anonymous', 'ldap', 'active', 'confirmed', 'online'], 'integer'],
      [['namedId', 'password'], 'string', 'max' => 50],
      [['name'], 'string', 'max' => 100],
      [['email'], 'string', 'max' => 255],
      [['token'], 'string', 'max' => 32],
      [['namedId','token'], 'unique'],
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'id' => Yii::t('app', 'ID'),
      'namedId' => Yii::t('app', 'Login name'),
      'created' => Yii::t('app', 'Created'),
      'modified' => Yii::t('app', 'Modified'),
      'name' => Yii::t('app', 'Full Name'),
      'password' => Yii::t('app', 'Password'),
      'email' => Yii::t('app', 'Email'),
      'anonymous' => Yii::t('app', 'Anonymous'),
      'ldap' => Yii::t('app', 'Ldap'),
      'active' => Yii::t('app', 'Active'),
      'lastAction' => Yii::t('app', 'Last Action'),
      'confirmed' => Yii::t('app', 'Confirmed'),
      'online' => Yii::t('app', 'Online'),
      'token' => Yii::t('app', 'Token'),
    ];
  }

  /**
   * @inheritdoc
   */
  public function getFormData()
  {
    return [
      'namedId' => [
        'enabled' => false
      ],
      'name' => [],
      'email' => [
        'placeholder' => Yii::t('app', "Enter a valid Email address"),
        'validation' => [
          'validator' => "email"
        ]
      ],
      'password' => [
        'type' => "PasswordField",
        'value' => "",
        'placeholder' => Yii::t('app', "To change the password, enter new password."),
        'unmarshal' => function ($value) {
          return $this->checkFormPassword($value);
        }

      ],
      'password2' => [
        'type' => "PasswordField",
        'value' => "",
        'ignore' => true,
        'placeholder' => Yii::t('app', "To change the password, repeat new password")
      ]
    ];
  }



  //-------------------------------------------------------------
  // Indentity Interface
  //-------------------------------------------------------------

  /**
   * @inheritdoc
   */
  public static function findIdentity($id)
  {
    return static::findOne($id);
  }

  /**
   * @inheritdoc
   */
  public static function findIdentityByAccessToken($token, $type = null)
  {
    return static::findOne(['token' => $token]);
  }

  /**
   * @inheritdoc
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @inheritdoc
   */
  public function getAuthKey()
  {
    return $this->token;
  }

  /**
   * @inheritdoc
   */
  public function validateAuthKey($authKey)
  {
    return $this->token === $authKey;
  }

  /**
   * Adds an access token for the user before the record is saved to the database
   *
   * @inheritdoc
   * @throws \yii\base\Exception
   */
  public function beforeSave($insert)
  {
    if (parent::beforeSave($insert)) {
      if ($this->isNewRecord or !$this->token) {
        $this->token = Yii::$app->security->generateRandomString();
      }
      return true;
    }
    return false;
  }

  /**
   * Before deleting of this record, remove sessions and user configs
   * @return bool
   */
  public function beforeDelete()
  {
    foreach($this->getSessions()->all() as $session) {
      try {
        $session->delete();
      } catch (\Throwable $e) {
        Yii::warning($e->getMessage());
      }
    }
    foreach($this->getUserConfigs()->all() as $userConfig) {
      try {
        $userConfig->delete();
      } catch (\Throwable $e) {
        Yii::warning($e->getMessage());
      }
    }
    return parent::beforeDelete();
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * Internal. Populated temporarily during relational queries.
   * @var int|null
   */
  public $groupId = null;

  /**
   * Returns User_Role records
   * @todo this needs to be looked at since this method AND the getRoles() method filters by group id
   * @return ActiveQuery
   */         
  public function getUserRoles()
  {
    $link = [
      'UserId'  => 'id',
      'GroupId' => 'groupId'
    ];
    return $this->hasMany(User_Role::class, $link );
  } 

  /**
   * Returns the roles of the user, depending on the groupId property.
   * Do not use directly unless you set the groupId property first.
   * @return ActiveQuery
   */
  public function getRoles()
  {
    return $this
      ->hasMany( Role::class, ['id' => 'RoleId'] )
      ->via('userRoles', function(ActiveQuery $query){
        return $query->andWhere(['GroupId' => $this->groupId]);
      });
  }

  /**
   * Returns the global roles of the user
   * @return ActiveQuery
   */ 
  public function getGlobalRoles()
  {
    return $this->getGroupRoles(null);
  }

  /**
   * @param Group|string|int|null $group
   *    If given, retrieve only the roles that the user has in the
   *    group with the given (numeric) id. Otherwise, return global roles only
   * @return ActiveQuery
   */       
  public function getGroupRoles($group=null)
  {
    if( $group instanceof Group ) {
      $groupId = $group->id;
    } elseif (is_string($group)){
      $groupObj = Group::findByNamedId($group);
      if ($groupObj === null){
        throw new InvalidArgumentException("Group '$group' does not exist");
      }
      $groupId=$groupObj->id;
    } elseif ( is_null($group) or is_int($group) ){
      $groupId = $group;
    } else {
      throw new InvalidArgumentException("Argument must be null, string, integer or instanceof Group");
    }
    return Role::find()
      ->joinWith('roleUsers')
      ->where([ 'GroupId' => $groupId, 'UserId' => $this->id ]);
  }

  /**
   * @return ActiveQuery
   */      
  protected function getUserGroups()
  {
    $userGroups = $this->hasMany(Group_User::class, ['UserId' => 'id']);
    return $userGroups;
  }

  /**
   * @return ActiveQuery
   */      
  public function getGroups()
  {
    return $this
      ->hasMany(Group::class, ['id' => 'GroupId'])
      ->via('userGroups');
  } 

  /**
   * @return ActiveQuery
   */    
  protected function getUserConfigs()
  {
    return $this->hasMany(UserConfig::class, ['UserId' => 'id']);
  } 

  /**
   * @return ActiveQuery
   */  
  public function getSessions()
  {
    return $this->hasMany(Session::class, ['UserId' => 'id']);
  } 

  /**
   * @return ActiveQuery
   */
  protected function getUserDatasources()
  {
    return $this->hasMany(Datasource_User::class, ['UserId' => 'id']);
  }

  /**
   * @return ActiveQuery
   */
  public function getDatasources()
  {
    return $this->hasMany(Datasource::class, ['id' => 'DatasourceId'])
      ->via('userDatasources');
  } 

  //-------------------------------------------------------------
  // Attributes/Getters
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
    return (boolean) $this->online;
  }

  /**
   * Returns true if the user is authenticated via an ldap source
   *
   * @return boolean
   */
  public function isLdapUser()
  {
    return (boolean) $this->ldap;
  }

  //-------------------------------------------------------------
  // Access Control
  //-------------------------------------------------------------

  public function isAdmin()
  {
    return in_array("admin", $this->getRoleNames());
  }

  /**
   * Checks if the current user has the given global permission.
   * respects wildcards, i.e. myapp.permissions.*. If a group object
   * is passed as second argument, it will check if the user has the
   * permission in that group.
   * @param string $requestedPermission the permission to check
   * @param Group|int|null $group
   *    If Group or integer, check against the permissions granted in that group.
   *    If null or no argument, check against global roles the user has.
   * @param Datasource|null $datasource
   *    If given, add all permissions the user has by way of being member of a 
   *    group that has access to the datasource.
   * @return bool
   * @throws InvalidArgumentException
   */
  public function hasPermission($requestedPermission, $group=null, Datasource $datasource=null)
  {
    if (is_integer($group) ){
      $group = Group::findOne($group);
      if( ! $group ) throw new InvalidArgumentException("Invalid group id #$group");
    } elseif (!($group instanceof Group or is_null($group))) {
      throw new InvalidArgumentException("Second argument must be null, string or instanceof Group");
    }
    return $this->_hasPermission($requestedPermission, $group, $datasource );
  }

  /**
   * The implementation of hasPermission
   * @param $requestedPermission
   * @param Group|int|null $group
   *    If Group or integer, check against the permissions granted in that group.
   *    If null or no argument, check against global roles the user has.
   * @param Datasource|null $datasource
   *    If given, add all permissions the user has by way of being member of a 
   *    group that has access to the datasource.
   * @return bool
   */
  protected function _hasPermission($requestedPermission, $group=null, Datasource $datasource=null)
  {
    $permissions = $this->getAllPermissionNames($group, $datasource);

    // use wildcard?
    $useWildcard = strstr($requestedPermission, "*");

    // check if permission is granted
    foreach ($permissions as $permission) {
      // global do anything permission
      if( $permission === "*" ){
        return true;
      }
      // exact match
      if ($permission === $requestedPermission) {
        return true;
      } 
      // else if the current permission name contains a wildcard
      elseif (($pos = strpos($permission, "*")) !== false) {
        if (substr($permission, 0, $pos) == substr($requestedPermission, 0, $pos)) {
          return true;
        }
      } 
      // else if the requested permission contains a wildcard
      elseif ($useWildcard and ($pos = strpos($requestedPermission, "*")) !== false) {
        if (substr($permission, 0, $pos) == substr($requestedPermission, 0, $pos)) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Whether the user has the given global (or group) role
   * @param string $role
   * @param Group|int|null $group
   * @return bool
   */
  public function hasRole($role, $group = null)
  {
    return in_array($role, $this->getRoleNames($group));
  }

  /**
   * Returns list of roles that a user has, either globally or in a group.
   * @param Group|string|int|null $group
   *    If given, retrieve only the roles that the user has in the
   *    group with the given id. Otherwise, return global roles only
   * @return string[]
   *    Array of role names
   */
  public function getRoleNames($group = null)
  {
    $roleNames = [];
    $roleObjects = $this->getGroupRoles($group)->all();
    foreach( $roleObjects as $obj) $roleNames[] = $obj->namedId;
    // if in group, add global roles
    if ( $group and $this->isMemberOf($group)){
      $roleNames = array_merge($roleNames, $this->getRoleNames());
    }
    return array_unique($roleNames);
  }

  /**
   * Returns list of groups that a user belongs to.
   *
   * @return string[]
   *    Array of string values: group named ids.
   */
  public function getGroupNames()
  {
    $result = $this->getGroups()->all();
    if (!is_array($result)) return [];
    return array_map( function($o) {return $o->namedId;}, $result );
  }

  /**
   * Returns true if user is member of that group
   * @param Group|string|int $group
   * @return bool
   */
  public function isMemberOf( $group )
  {
    if( is_string($group) ) {
      $group = Group::findByNamedId($group);
    } elseif ( is_int($group) ){
      $group = Group::findOne($group);
    } elseif( !( $group instanceof Group)) {
      throw new InvalidArgumentException("Argument must be string or instanceof Group, is " . describe_type($group));
    }
    if ($group === null) {
      throw new InvalidArgumentException("Group '$group' does not exist");
    }
    return in_array($group->namedId, $this->getGroupNames());
  }

  /**
   * Returns the names of all permissions the user has globally AND in the
   * given group
   * @param Group|int|null $group
   *    If given, return the permissions that the user has in the given
   *    group model instance, which can also be specified with a (numeric) id.
   *    If the application is configured to only use global roles, the
   *    group is ignored.
   *    If not given or null, return only global permissions
   * @param Datasource|null $datasource
   *    If given, add all permissions the user has by way of being member of a 
   *    group that has access to the datasource.
   * @return array
   */
  public function getAllPermissionNames($group=null, Datasource $datasource=null)
  {
    // global roles
    $permissions = $this->getPermissionNames(null);
    if ($group and ! Yii::$app->config->getIniValue("global_roles_only") ){
      // add group-specific roles
      if (!($group instanceof Group or is_int($group))) {     
        throw new \InvalidArgumentException("Argument must be instanceof Group, integer, null or 'all'");
      }
      $permissions = array_merge(
         $permissions,
         $this->getPermissionNames($group)
      );
    }
    // if the user has access to the given database, find the role(s) that the user has in this
    // database and add the corresponding permissions
    // TODO This is a complex algorithm. Transform this into elegant Yii2 ActiveQuery code & into a method
    if ($datasource and in_array($datasource->namedId, $this->accessibleDatasourceNames)) {
      $datasourceRoleIds  = $datasource->getRoles()->select('id')->column();
      $datasourceGroupIds = $datasource->getGroups()->select('id')->column();
      $query = User_Role::find()
        ->select('RoleId')
        ->where(['UserId'=>$this->id])
        ->distinct();
      if (count($datasourceGroupIds) and count($datasourceRoleIds)) {
        $query = $query->andWhere("RoleId in (" . implode(",", $datasourceRoleIds) . ") or GroupId in (" . implode(",", $datasourceGroupIds) . ") or GroupId is NULL");
      } else if (!count($datasourceGroupIds) and count($datasourceRoleIds)){
        $query = $query->andWhere("RoleId in (" . implode(",",$datasourceRoleIds) .") or GroupId is NULL");
      } else if (count($datasourceGroupIds) and ! count($datasourceRoleIds)){
        $query = $query->andWhere("GroupId in (" . implode(",",$datasourceGroupIds) .") or GroupId is NULL");
      } else {
        $query = $query->andWhere("GroupId is NULL");
      }
      $userRoleIds = $query->column();
      $dsGlobalRoleIds = array_filter(
        $datasourceRoleIds,
        function($id){return $this->hasRole(Role::findOne($id)->namedId);}
      );
      $roleIds = array_unique(array_merge($userRoleIds,$dsGlobalRoleIds));
      foreach ($roleIds as $id) {
        $role = Role::findOne($id);
        foreach ($role->permissions as $permission) {
          $permissions[] = $permission->namedId;
        }
      }
    }
    $permissions = array_values(array_unique($permissions));
//    if( $datasource) {
//      $userGlobalRoles = $this->getGlobalRoles()->select('data_Role.id')->column();
//      Yii::debug([
//        'datasource' => [$datasource->namedId, $datasource->id],
//        'user' => [$this->namedId, $this->id],
//        "user's global role ids" => $userGlobalRoles,
//        'role ids attached to datasource:' => $datasourceRoleIds,
//        'group ids attached to datasource:' => $datasourceGroupIds,
//        'role ids via global roles and groups that are attached to the database' => $userRoleIds,
//        'datasource role ids filtered by user`s global roles' => $dsGlobalRoleIds,
//        'combined role ids' => $roleIds,
//        'permissions' => $permissions
//      ]);
//    }
    return $permissions;
  }

  /**
   * Returns list of permissions that the user has in the given group OR globally
   * @param Group|int|null $group
   *    If given, return ONLY the permissions that the user has in the
   *    group, which can also be specified with the given (numeric) id.
   *    Otherwise, return global permissions only.
   * @return string[]
   *    Array of permission names
   */
  public function getPermissionNames( $group=null )
  {
    $permissions = [];
    /** @var Role[] $roles */
    $roles = $this->getGroupRoles($group)->all();
    //$groupName = $group instanceof Group ? $group->name : ( is_int($group) ? Group::findOne($group)->name : "globally");
    //Yii::info("{$this->namedId} has " . count( $roles). " roles in group {$groupName}");
    foreach( $roles as $role) {
      //Yii::info("{$this->namedId} has role {$role->namedId} in group {$groupName}");
      $permissions = array_merge( $permissions, $role->getPermissionNames() );
    }
    return array_values(array_unique($permissions));
  }

  /**
   * Returns list of datasources that the user has access to 
   * @return string[]
   *    Array of datasource names
   */
  public function getAccessibleDatasourceNames()
  {
    // admin has access to all datasources
    if( $this->hasRole("admin")){
      return Datasource::find()
        ->select('namedId')
        ->column();
    }
    // others have only limited access
    $datasourceNames = [];
    // use the (active) datasources that are linked to the user directly
    $myDatasources = $this->getDatasources()->where(['active'=>1])->all();
    foreach( $myDatasources as $o ) {
      $datasourceNames[] = $o->namedId;
    }

    // now add those which are linked to the (active) groups that the user belongs to 
    // or through the roles that this goup gives to the user, including "global" roles
    $groups = $this->getGroups()->where(['active'=>1])->all();
    $groups[] = null; // global group

    /** @var Group $group */
    foreach( $groups as $group){
      // add group datasources
      if ($group) foreach ($group->datasourceNames as $name) $datasourceNames[] = $name;
      // add datasources of the users group/global roles
      /** @var Role $role */
      foreach ($this->getGroupRoles($group)->all() as $role){
        /** @var Datasource $datasource */
        foreach ($role->datasources as $datasource){
          $datasourceNames[] = $datasource->namedId;
        }
      }
    }
    // finally, add the datasources that are accessible to the anonymous role
    /** @var Datasource $datasource */
    foreach( Role::findByNamedId('anonymous')->getDatasources()->all() as $datasource){
      $datasourceNames[] = $datasource->namedId;
    }
    $names = array_unique($datasourceNames);
    sort($names);
    return $names;
  }  

  /**
   * Resets the timestamp of the last action  for the current user
   * @return void
   */
  public function resetLastAction()
  {
    throw new \BadMethodCallException("not implemented");
  }

  /**
   * Unmarshaler for the password field
   * @param $value
   */
  protected function checkFormPassword( $value ){
    // TODO
    return $value;
  }
}