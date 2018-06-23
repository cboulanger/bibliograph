<?php

namespace app\models;

use app\modules\converters\export\AbstractExporter;
use InvalidArgumentException;
use lib\models\BaseModel;
use Yii;

/**
 * This is the model class for table "data_ExportFormat".
 *
 * @property string $namedId
 * @property string $class
 * @property string $name
 * @property string $description
 * @property integer $active
 * @property string $type
 * @property string $extension
 */
class ExportFormat extends BaseModel
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_ExportFormat';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['active'], 'integer'],
      [['namedId'], 'string', 'max' => 50],
      [['class', 'name'], 'string', 'max' => 100],
      [['description'], 'string', 'max' => 255],
      [['type', 'extension'], 'string', 'max' => 20],
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
      'class' => Yii::t('app', 'Class'),
      'name' => Yii::t('app', 'Name'),
      'description' => Yii::t('app', 'Description'),
      'active' => Yii::t('app', 'Active'),
      'type' => Yii::t('app', 'Type'),
      'extension' => Yii::t('app', 'Extension'),
    ];
  }

  /**
   * Creates an exporter object
   * @param string $format
   * @return AbstractExporter
   * @throws InvalidArgumentException
   */
  public static function createExporter($format)
  {
    $instance = self::findByNamedId($format);
    if( ! $instance ){
      throw new InvalidArgumentException("Format '$format' does not exist.");
    }
    $class = $instance->class;
    if( ! class_exists($class) /* todo: check interface */ ){
      throw new InvalidArgumentException("Exporter class '$class' of format '$format' is invalid.");
    }
    return new $class();
  }
}
