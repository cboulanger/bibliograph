<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data_UserConfig".
 *
 * @property integer $id
 * @property string $value
 * @property string $created
 * @property string $modified
 * @property integer $UserId
 * @property integer $ConfigId
 */
class UserConfig extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'data_UserConfig';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified'], 'safe'],
            [['UserId', 'ConfigId'], 'integer'],
            [['value'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'value' => Yii::t('app', 'Value'),
            'created' => Yii::t('app', 'Created'),
            'modified' => Yii::t('app', 'Modified'),
            'UserId' => Yii::t('app', 'User ID'),
            'ConfigId' => Yii::t('app', 'Config ID'),
        ];
    }
}
