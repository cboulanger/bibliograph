<?php

namespace app\models;

use Yii;

use app\models\BaseModel;
use app\models\UserConfig;

/*
 * constants
 */
define( "QCL_CONFIG_TYPE_STRING", "string");
define( "QCL_CONFIG_TYPE_NUMBER", "number");
define( "QCL_CONFIG_TYPE_BOOLEAN", "boolean");
define( "QCL_CONFIG_TYPE_LIST", "list");


/**
 * This is the model class for table "data_Config".
 *
 * @property integer $id
 * @property integer $type
 * @property string $default
 * @property integer $customize
 * @property integer $final
 * @property string $namedId
 * @property string $created
 * @property string $modified
 */
class Config extends BaseModel
{

  const value_types = array("string","number","boolean","list");
  
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
      [['created', 'modified'], 'safe'],
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
  protected function getUserConfigs( $userId )
  {
    return $this
      ->hasMany(UserConfig::className(), ['ConfigId' => 'id'])
      ->onCondition( ['UserId' => $userId ]);
  }

  /**
   * @param int $userId
   * @return \app\models\UserConfig|null 
   *    Returns the instance of the UserConfig linked to the particular user or null
   *    if none exists
   * @throws LogicException
   */
  public function getUserConfig( $userId )
  {
    if( ! $this->customize ) {
      throw new LogicException("Config entry {$this->namedId} cannot be customized.");
    }
    $query = $this->getUserConfigs( $userId );
    //codecept_debug($query->createCommand()->getRawSql());
    $result = $query->one();
    return $result;
  }

  /**
   * Returns the customized user configuration
   *
   * @param int $userId
   * @return mixed
   * @throws \InvalidArgumentException if no user configuration exists
   */
  public function getUserConfigValue( $userId ){
    $userConfig = $this->getUserConfig( $userId );
    if( is_null($userConfig) ){
      throw new \InvalidArgumentException("No user configuration for {$this->namedId}.");
    }
    return $userConfig->value;
  }
}