<?php

namespace app\models;

use Yii;

use lib\models\BaseModel;
use app\models\UserConfig;

/**
 * This is the model class for table "data_Config".
 *
 * @property int $type
 * @property string $default
 * @property int $customize
 * @property int $final
 * @property string $namedId
 */
class Config extends BaseModel
{

  const value_types = [
    0 => "string",
    1 => "number",
    2 => "boolean",
    3 => "list"
  ];

  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_Config';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['type', 'customize', 'final'], 'integer'],
      [['default'], 'string', 'max' => 255],
      [['namedId'], 'string', 'max' => 50],
      [['namedId'], 'unique'],
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'id' => 'ID',
      'type' => 'Type',
      'default' => 'Default',
      'customize' => 'Customize',
      'final' => 'Final',
      'namedId' => 'Named ID',
      'created' => 'Created',
      'modified' => 'Modified',
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * @param int $userId
   * @return \yii\db\ActiveQuery
   */
  protected function getUserConfigs($userId)
  {
    return $this
      ->hasMany(UserConfig::className(), ['ConfigId' => 'id'])
      ->onCondition(['UserId' => $userId]);
  }

  /**
   * @param int|\app\models\User $user Either a numeric id or the user model
   * @return \app\models\UserConfig|null
   *    Returns the instance of the UserConfig linked to the particular user or
   *   null if none exists
   * @throws \LogicException
   */
  public function getUserConfig($user)
  {
    $userId = $user instanceof \app\models\User ? $user->id : $user;
    if (!is_numeric($userId)) throw new \InvalidArgumentException("Invalid user/user id");
    $query = $this->getUserConfigs($userId);
    //codecept_debug($query->createCommand()->getRawSql());
    $result = $query->one();
    return $result;
  }

  /**
   * Returns the customized user configuration or the config default if no
   * user object has been passed.
   *
   * @param int|\app\models\User|null $user Either a numeric id or the user
   *   model
   * @return mixed
   */
  public function getUserConfigValue($user)
  {
    if (!$user) {
      return $this->default;
    }
    $userConfig = $this->getUserConfig($user);
    if (is_null($userConfig)) {
      // no user config exists, return default value
      return $this->default;
    }
    return $userConfig->value;
  }
}
