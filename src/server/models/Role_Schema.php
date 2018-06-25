<?php

namespace app\models;

/**
 * This is the model class for table "join_Permission_Role".
 *
 * @property string $created
 * @property string $modified
 * @property integer $RoleId
 * @property integer $PermissionId
 */
class Role_Schema extends \lib\models\BaseJunctionModel
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'join_Role_Schema';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified'], 'safe'],
      [['RoleId', 'SchemaId'], 'integer'],
      [['RoleId', 'SchemaId'], 'unique', 'targetAttribute' => ['RoleId', 'SchemaId'], 'message' => 'The combination of Role ID and Schema ID has already been taken.'],
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'created' => 'Created',
      'modified' => 'Modified',
      'RoleId' => 'Role ID',
      'SchemaId' => 'Schema ID',
    ];
  }
}