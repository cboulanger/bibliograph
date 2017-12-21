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

  protected function getUserConfigs()
  {
    return $this->hasMany(UserConfig::className(), ['ConfigId' => 'id']);
  }

}
