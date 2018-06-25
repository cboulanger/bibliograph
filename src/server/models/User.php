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

use lib\exceptions\UserErrorException;
use Yii;
use yii\db\StaleObjectException;
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
      [['created', 'modified', 'lastAction'], 'safe'],
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
   * @return \yii\db\ActiveQuery
   */         
  public function getUserRoles()
  {
    return $this->hasMany(User_Role::class, ['UserId' => 'id'] );
  } 

  /**
   * Returns the global roles of the user.
   * @return \yii\db\ActiveQuery
   */
  public function getRoles()
  {
    return $this->getGroupRoles(null);
  }

  /**
   * @param int|null $groupId
   *    If given, retrieve only the roles that the user has in the
   *    group with the given (numeric) id. Otherwise, return global roles only
   * @return \yii\db\ActiveQuery
   */       
  public function getGroupRoles(int $groupId=null)
  {
  return $this->hasMany(Role::class, ['id' => 'RoleId' ])
    ->via('userRoles', function( yii\db\ActiveQuery $query) use($groupId) {
    return $query->andWhere(['groupId'=>$groupId]);
    });
  }

  /**
   * @return \yii\db\ActiveQuery
   */      
  protected function getUserGroups()
  {
    $userGroups = $this->hasMany(Group_User::class, ['UserId' => 'id']);
    return $userGroups;
  }

  /**
   * @return \yii\db\ActiveQuery
   */      
  public function getGroups()
  {
    return $this->hasMany(Group::class, ['id' => 'GroupId'])
      ->via('userGroups');
  } 

  /**
   * @return \yii\db\ActiveQuery
   */    
  protected function getUserConfigs()
  {
    return $this->hasMany(UserConfig::class, ['UserId' => 'id']);
  } 

  /**
   * @return \yii\db\ActiveQuery
   */  
  public function getSessions()
  {
    return $this->hasMany(Session::class, ['UserId' => 'id']);
  } 

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getUserDatasources()
  {
    return $this->hasMany(Datasource_User::class, ['UserId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  public function getDatasources()
  {
    return $this->hasMany(Datasource::class, ['id' => 'DatasourceId'])
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
    return $this->_hasPermission($requestedPermission);
  }

  /**
   * The implementation of hasPermission
   * @param $requestedPermission
   * @return bool
   */
  protected function _hasPermission($requestedPermission)
  {
    // get all permissions of the user
    $permissions = $this->getAllPermissionNames();
    
    // use wildcard?
    $useWildcard = strstr($requestedPermission, "*");

    // check if permission is granted
    foreach ($permissions as $permission) {
      // global do anything permission
      if( $permission == "*" ){
        return true;
      }
      // exact match
      if ($permission == $requestedPermission) {
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
  public function getGroupNames()
  {
    $result = $this->getGroups()->all();
    if( is_null( $result ) ) return [];
    return array_map( function($o) {return $o->namedId;}, $result );
  }

  /**
   * Returns the names of all permissions the user has (globally or in groups)
   * FIXME This needs a rewrite since it gives the user all the permissions that s/he has in one group in all other groups!
   * @return array
   */
  public function getAllPermissionNames()
  {
    // global roles
    $permissions= array_values($this->getPermissionNames());
    // group roles
    if( ! Yii::$app->config->getIniValue("global_roles_only") ){
      /** @var Group $group */
      foreach( $this->getGroups()->all() as $group ){
        $permissions=array_merge($permissions, $this->getPermissionNames($group->id) );
      }
    }
    return $permissions;
  }

  /**
   * Returns list of permissions that the user has in the given group OR globally
   * @param int|null $groupId
   *    If given, retrieve only the permissions that the user has in the
   *    group with the given (numeric) id. Otherwise, return global permissions only
   * @return string[]
   *    Array of permission names
   */
  public function getPermissionNames( int $groupId = null )
  {
    $permissionNames = [];
    $roles = $this->getGroupRoles( $groupId )->all();
    if( is_array($roles) ) {
      /** @var Role $role */
      foreach( $roles as $role) {
        $permissionNames = array_merge( $permissionNames, $role->getPermissionNames() );
      }
    }
    return array_unique($permissionNames);
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
    $myDatasources = $this->getDatasources()->where(['active'=>1])->all();
    if( is_array($myDatasources) ) {
      foreach( $myDatasources as $o ) {
        $datasourceNames[] = $o->namedId;
      }
    }
    $groups = $this->getGroups()->where(['active'=>1])->all();
    if( is_array($groups) ) {
      foreach( $groups as $group){
        $datasourceNames = array_merge( $datasourceNames, $group->getDatasourceNames());
        $roles = $this->getGroupRoles( $group->id )->all();
        if( is_array($roles) ) foreach( $roles as $role) {
          $datasourceNames = array_merge( $datasourceNames, $role->getDatasourceNames() );
        }
      }
    }
    $roles = $this->getRoles()->where(['active'=>1])->all();
    if( is_array($roles) ) {
      foreach( $roles as $role){
        $datasourceNames = array_merge( $datasourceNames, $role->getDatasourceNames());
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
    throw new \BadMethodCallException("not implemented");
  }
}
