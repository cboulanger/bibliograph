<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data_ExportFormat".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $class
 * @property string $name
 * @property string $description
 * @property integer $active
 * @property string $type
 * @property string $extension
 */
class ExportFormat extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'data_ExportFormat';
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
            [['class', 'name'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 255],
            [['type', 'extension'], 'string', 'max' => 20],
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
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
            'active' => Yii::t('app', 'Active'),
            'type' => Yii::t('app', 'Type'),
            'extension' => Yii::t('app', 'Extension'),
        ];
    }
}
