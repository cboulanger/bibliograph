<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "join_Datasource_User".
 *
 * @property integer $DatasourceId
 * @property integer $UserId
 */
class Datasource_User extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'join_Datasource_User';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['DatasourceId', 'UserId'], 'integer'],
            [['DatasourceId', 'UserId'], 'unique', 'targetAttribute' => ['DatasourceId', 'UserId'], 'message' => 'The combination of Datasource ID and User ID has already been taken.'],
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
            'UserId' => 'User ID',
        ];
    }
}
