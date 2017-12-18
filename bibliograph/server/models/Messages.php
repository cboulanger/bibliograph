<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data_Messages".
 *
 * @property integer $id
 * @property string $created
 * @property string $modified
 * @property string $name
 * @property resource $data
 * @property integer $SessionId
 */
class Messages extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'data_Messages';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified'], 'safe'],
            [['data'], 'string'],
            [['SessionId'], 'integer'],
            [['name'], 'string', 'max' => 100],
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
            'name' => 'Name',
            'data' => 'Data',
            'SessionId' => 'Session ID',
        ];
    }
}
