<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "join_Datasource_Role".
 *
 * @property integer $id
 * @property string $created
 * @property string $modified
 * @property integer $DatasourceId
 * @property integer $RoleId
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
            [['created', 'modified'], 'safe'],
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
