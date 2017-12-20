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

use app\models\Permissions;
use app\models\Roles;
use app\models\User_Role;
use Yii;

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
class Role extends \yii\db\ActiveRecord
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
      'namedId' => Yii::t('app', 'Named ID'),
      'created' => Yii::t('app', 'Created'),
      'modified' => Yii::t('app', 'Modified'),
      'name' => Yii::t('app', 'Name'),
      'description' => Yii::t('app', 'Description'),
      'active' => Yii::t('app', 'Active'),
    ];
  }

  public function getFormData(){
    return [
      'name'        => array(
        'label'       => $this->tr("Name")
      ),
      'description' => array(
        'label'       => $this->tr("Description")
      )
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  protected function getRolePermissions()
  {
    return $this->hasMany(Permission_Role::className(), ['RoleId' => 'id']);
  }

  protected function getPermissions()
  {
    return $this->hasMany(Permission::className(), ['id' => 'PermissionId'])->via('rolePermissions');
  }

  protected function getRoleUsers()
  {
    return $this->hasMany(User_Role::className(), ['RoleId' => 'id']);
  }

  protected function getUsers()
  {
    return $this->hasMany(User::className(), ['id' => 'UserId'])->via('roleUsers');
  }  

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------
  

  /**
   * Returns a list of permissions connected to the current model record.
   * @return array
   */
  public function permissions()
  {
    $permModel = $this->getPermissionModel();
    try
    {
      $permModel->findLinked( $this );
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      return array();
    }
    $permissions =  array();
    while ( $permModel->loadNext() )
    {
      $permissions[] = $permModel->namedId();
    }
    return $permissions;
  }

  /**
   * Returns a list of users connected to the current model record.
   * @return array
   */
  public function users()
  {
    $userModel = $this->getUserModel();
    $userModel->findLinked( $this );
    $users =  array();
    while ( $userModel->loadNext() )
    {
      $users[] = $userModel->namedId();
    }
    return $users;
  }  
}
