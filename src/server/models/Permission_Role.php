<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "join_Permission_Role".
 *
 * @property integer $id
 * @property string $created
 * @property string $modified
 * @property integer $RoleId
 * @property integer $PermissionId
 */
class Permission_Role extends \yii\db\ActiveRecord
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'join_Permission_Role';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified'], 'safe'],
      [['RoleId', 'PermissionId'], 'integer'],
      [['PermissionId', 'RoleId'], 'unique', 'targetAttribute' => ['PermissionId', 'RoleId'], 'message' => 'The combination of Role ID and Permission ID has already been taken.'],
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'id' => 'ID',
      'created' => 'Created',
      'modified' => 'Modified',
      'RoleId' => 'Role ID',
      'PermissionId' => 'Permission ID',
    ];
  }
}
