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

use lib\models\BaseModel;
use app\models\Permission;
use app\models\Datasources;
use app\models\User;

use app\models\User_Role;
use app\models\Permission_Role;


/**
 * This is the model class for table "data_Role".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $name
 * @property string $description
 * @property integer $active
 */
class Role extends BaseModel
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_Role';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified'], 'safe'],
      [['active'], 'integer'],
      [['namedId'], 'string', 'max' => 50],
      [['name', 'description'], 'string', 'max' => 100],
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
      'namedId' => Yii::t('app', 'Role ID'),
      'created' => Yii::t('app', 'Created'),
      'modified' => Yii::t('app', 'Modified'),
      'name' => Yii::t('app', 'Name'),
      'description' => Yii::t('app', 'Description'),
      'active' => Yii::t('app', 'Active'),
    ];
  }

  public function getFormData(){
    return [
      'namedId'     => [],
      'name'        => [],
      'description' => []
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * @return \yii\db\ActiveQuery
   */           
  protected function getRolePermissions()
  {
    return $this->hasMany(Permission_Role::className(), ['RoleId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */ 
  public function getPermissions()
  {
    return $this->hasMany(Permission::className(), ['id' => 'PermissionId'])->via('rolePermissions');
  }

  /**
   * @return \yii\db\ActiveQuery
   */ 
  protected function getRoleUsers()
  {
    return $this->hasMany(User_Role::className(), ['RoleId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */ 
  protected function getUsers()
  {
    return $this->hasMany(User::className(), ['id' => 'UserId'])->via('roleUsers');
  }  

  /**
   * @return \yii\db\ActiveQuery
   */ 
  protected function getRoleDatasources()
  {
    return $this->hasMany(Datasource_Role::className(), ['RoleId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */ 
  public function getDatasources()
  {
    return $this->hasMany(Datasource::className(), ['id' => 'DatasourceId'])->via('roleDatasources');
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------
  
  /**
   * Returns the usernames of the users with this role
   * @return string[]
   */           
  public function getUserNames()
  {
    $result = $this->getUsers()->all();
    if( is_null( $result ) ) return [];
    return array_map( function($o) {return $o->namedId;}, $result );
  } 

  /**
   * Returns the names of the datasources that are accessible to this role
   * @return string[]
   */
  public function getDatasourceNames()
  {
    $result = $this->getDatasources()->all();
    if( is_null( $result ) ) return [];
    return array_map( function($o) {return $o->namedId;}, $result );
  } 

  /**
   * Returns the names of permissions connected to the active record.
   * @return string[]
   */
  public function getPermissionNames()
  {
    $result = $this->getPermissions()->all();
    if( is_null( $result ) ) return [];
    return array_map( function($o) {return $o->namedId;}, $result );
  }

  /**
   * Returns true if the role includes the given permission
   *
   * @param string $permission The name of the permission
   * @return boolean
   */
  public function hasPermission( $permission )
  {
    return in_array( $permission, $this->getPermissionNames() );
  }
}
