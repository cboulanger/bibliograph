<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data_Plugin".
 *
 * @property string $namedId
 * @property string $name
 * @property string $description
 * @property string $data
 * @property int $active
 */
class Plugin extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'data_Plugin';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['data'], 'string'],
            [['active'], 'integer'],
            [['namedId'], 'string', 'max' => 50],
            [['name'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 250],
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
            'data' => Yii::t('app', 'Data'),
            'active' => Yii::t('app', 'Active'),
        ];
    }
}
