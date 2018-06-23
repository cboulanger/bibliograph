<?php

namespace app\models;

use Yii;

use lib\models\BaseModel;
use app\models\User;
use app\models\Config;


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
class UserConfig extends BaseModel
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

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  public function getUser()
  {
    return $this->hasOne(User::className(), ['id' => 'UserId']);
  }

  public function getConfig()
  {
    return $this->hasOne(Config::className(), ['id' => 'ConfigId']);
  }
}
