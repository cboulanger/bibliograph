<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "join_Group_User".
 *
 * @property int $UserId
 * @property int $GroupId
 */
class Group_User extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'join_Group_User';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['UserId', 'GroupId'], 'integer'],
            [['GroupId', 'UserId'], 'unique', 'targetAttribute' => ['GroupId', 'UserId'], 'message' => 'The combination of User ID and Group ID has already been taken.'],
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
            'UserId' => 'User ID',
            'GroupId' => 'Group ID',
        ];
    }
}
