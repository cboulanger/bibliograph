<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data_Session".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $parentSessionId
 * @property string $ip
 * @property integer $UserId
 */
class Session extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'data_Session';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified'], 'safe'],
            [['UserId'], 'integer'],
            [['namedId', 'parentSessionId'], 'string', 'max' => 50],
            [['ip'], 'string', 'max' => 32],
            [['namedId'], 'unique'],
            [['namedId', 'ip'], 'unique', 'targetAttribute' => ['namedId', 'ip'], 'message' => 'The combination of Named ID and Ip has already been taken.'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'namedId' => 'Named ID',
            'created' => 'Created',
            'modified' => 'Modified',
            'parentSessionId' => 'Parent Session ID',
            'ip' => 'Ip',
            'UserId' => 'User ID',
        ];
    }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------
  
  public function getUser()
  {
    return $this->hasOne(User::className(), ['id' => 'UserId']);
  }
}
