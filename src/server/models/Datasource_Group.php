<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "join_Datasource_Group".
 *
 * @property integer $DatasourceId
 * @property integer $GroupId
 */
class Datasource_Group extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'join_Datasource_Group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['DatasourceId', 'GroupId'], 'integer'],
            [['DatasourceId', 'GroupId'], 'unique', 'targetAttribute' => ['DatasourceId', 'GroupId'], 'message' => 'The combination of Datasource ID and Group ID has already been taken.'],
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
            'GroupId' => 'Group ID',
        ];
    }
}
