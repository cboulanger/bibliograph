<?php

namespace app\models;

/**
 * This is the model class for table "join_Datasource_Role".
 *
 * @property int $DatasourceId
 * @property int $RoleId
 */
class Datasource_Role extends \yii\db\ActiveRecord
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'join_Datasource_Role';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['DatasourceId', 'RoleId'], 'integer'],
      [['DatasourceId', 'RoleId'], 'unique', 'targetAttribute' => ['DatasourceId', 'RoleId'], 'message' => 'The combination of Datasource ID and Role ID has already been taken.'],
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
      'DatasourceId' => 'Datasource ID',
      'RoleId' => 'Role ID',
    ];
  }
}
