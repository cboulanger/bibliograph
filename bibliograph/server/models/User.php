<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data_User".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $name
 * @property string $password
 * @property string $email
 * @property integer $anonymous
 * @property integer $ldap
 * @property integer $active
 * @property string $lastAction
 * @property integer $confirmed
 * @property integer $online
 */
class User extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'data_User';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified', 'lastAction'], 'safe'],
            [['anonymous', 'ldap', 'active', 'confirmed', 'online'], 'integer'],
            [['namedId', 'password'], 'string', 'max' => 50],
            [['name'], 'string', 'max' => 100],
            [['email'], 'string', 'max' => 255],
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
            'password' => Yii::t('app', 'Password'),
            'email' => Yii::t('app', 'Email'),
            'anonymous' => Yii::t('app', 'Anonymous'),
            'ldap' => Yii::t('app', 'Ldap'),
            'active' => Yii::t('app', 'Active'),
            'lastAction' => Yii::t('app', 'Last Action'),
            'confirmed' => Yii::t('app', 'Confirmed'),
            'online' => Yii::t('app', 'Online'),
        ];
    }
}
