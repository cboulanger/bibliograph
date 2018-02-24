<?php

namespace app\models;

use lib\exceptions\RecordExistsException;
use Yii;

/**
 * This is the model class for table "data_DatasourceSchema".
 *
 * @property integer $id
 * @property string $namedId
 * @property string $created
 * @property string $modified
 * @property string $class
 * @property string $description
 * @property integer $active
 */
class Schema extends \lib\models\BaseModel
{

  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_Schema';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified'], 'safe'],
      [['active', 'protected'], 'integer'],
      [['namedId'], 'string', 'max' => 50],
      [['class', 'name'], 'string', 'max' => 100],
      [['description'], 'string', 'max' => 255],
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
      'created' => Yii::t('app', 'Created'),
      'modified' => Yii::t('app', 'Modified'),
      'namedId' => Yii::t('app', 'Named ID'),
      'name' => Yii::t('app', 'Name'),
      'description' => Yii::t('app', 'Description'),
      'class' => Yii::t('app', 'Class'),
      'active' => Yii::t('app', 'Active'),
      'protected' => Yii::t('app', 'Protected'),
    ];
  }

  public function getFormData()
  {
    return [
      'namedId' => [],
      'name' => [],
      'description' => []
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getDatasources()
  {
    return $this->hasMany(Datasource::class, ['schema' => 'namedId']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getRoleSchemas()
  {
    return $this->hasMany(Role_Schema::class, ['SchemaId' => 'id']);
  }

  /**
   * @return \yii\db\ActiveQuery
   */
  protected function getRoles()
  {
    return $this->hasMany(Role::class, ['id' => 'RoleId'])->via('roleSchemas');
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Registers a schema.
   * @param string $namedId
   * @param string $className
   *    Throws a \ReflectionException if the class does not exist.
   * @param array|null $options
   * @throws RecordExistsException
   * @throws  \InvalidArgumentException
   * @throws \ReflectionException
   */
  public static function register($namedId, string $className, array $options = null)
  {
    if (!$namedId or !is_string($namedId)) {
      throw new \InvalidArgumentException("Invalid namedId parameter");
    }
    $class = new \ReflectionClass($className);
    $baseClass = \app\models\Datasource::class;
    if (!$class->isSubclassOf($baseClass)) {
      throw new \InvalidArgumentException('Class must extend ' . $baseClass);
    }
    if (Schema::findByNamedId($namedId)) {
      throw new RecordExistsException("Schema '$namedId' already exists.");
    }
    try {
      $model = new static ([
        'namedId' => $namedId,
        'class' => $class->getName(),
        'name' => $class->getProperty('name')->getValue(),
        'description' => $class->getProperty('description')->getValue(),
      ]);
      if ($options) $model->setAttributes($options);
      $model->save();
    } catch (\Exception $e) {
      throw new \InvalidArgumentException($e->getMessage(), null, $e);
    }
  }
}
