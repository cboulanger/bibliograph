<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data_Datasource".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $title
 * @property string $description
 * @property string $schema
 * @property string $type
 * @property string $host
 * @property integer $port
 * @property string $database
 * @property string $username
 * @property string $password
 * @property string $encoding
 * @property string $prefix
 * @property string $resourcepath
 * @property integer $active
 * @property integer $readonly
 * @property integer $hidden
 */
class Datasource extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'data_Datasource';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified'], 'safe'],
            [['port', 'active', 'readonly', 'hidden'], 'integer'],
            [['namedId', 'username', 'password'], 'string', 'max' => 50],
            [['title', 'schema', 'database'], 'string', 'max' => 100],
            [['description', 'resourcepath'], 'string', 'max' => 255],
            [['type', 'encoding', 'prefix'], 'string', 'max' => 20],
            [['host'], 'string', 'max' => 200],
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
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'schema' => Yii::t('app', 'Schema'),
            'type' => Yii::t('app', 'Type'),
            'host' => Yii::t('app', 'Host'),
            'port' => Yii::t('app', 'Port'),
            'database' => Yii::t('app', 'Database'),
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
            'encoding' => Yii::t('app', 'Encoding'),
            'prefix' => Yii::t('app', 'Prefix'),
            'resourcepath' => Yii::t('app', 'Resourcepath'),
            'active' => Yii::t('app', 'Active'),
            'readonly' => Yii::t('app', 'Readonly'),
            'hidden' => Yii::t('app', 'Hidden'),
        ];
    }
}
