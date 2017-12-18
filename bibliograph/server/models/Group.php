<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data_Group".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $name
 * @property string $description
 * @property integer $ldap
 * @property string $defaultRole
 * @property integer $active
 */
class Group extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'data_Group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified'], 'safe'],
            [['ldap', 'active'], 'integer'],
            [['namedId'], 'string', 'max' => 50],
            [['name', 'description'], 'string', 'max' => 100],
            [['defaultRole'], 'string', 'max' => 30],
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
            'ldap' => Yii::t('app', 'Ldap'),
            'defaultRole' => Yii::t('app', 'Default Role'),
            'active' => Yii::t('app', 'Active'),
        ];
    }
}
