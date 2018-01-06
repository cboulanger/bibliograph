<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data_DatasourceSchema".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $class
 * @property string $description
 * @property integer $active
 */
class DatasourceSchema extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'data_DatasourceSchema';
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
            [['class'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 255],
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
            'class' => Yii::t('app', 'Class'),
            'description' => Yii::t('app', 'Description'),
            'active' => Yii::t('app', 'Active'),
        ];
    }
}
