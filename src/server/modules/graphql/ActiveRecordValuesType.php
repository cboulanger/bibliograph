<?php

namespace app\modules\graphql;

use app\controllers\traits\AuthTrait;
use app\controllers\traits\DatasourceTrait;
use Exception;
use GraphQL\Server\RequestError;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use lib\models\BaseModel;
use ReflectionClass;
use ReflectionException;
use Yii;
use yii\db\ActiveRecord;

/**
 *
 */
class ActiveRecordValuesType extends ActiveRecordType {

  /**
   * YiiModelType constructor.
   * @param $class
   * @param array|null $fields
   * @throws ReflectionException
   */
  function __construct($class, array $fields=null) {
    $fields = $fields ?? [
      'name' => get_called_class() . "<$class>",
      'fields' => [
        'row' => [
          'type' => Type::string(), //Type::listOf(Type::string()),
          'description' => 'A row of table data'
        ]
      ]
    ];
    parent::__construct($class, $fields);
  }

  /**
   * @param string $class
   * @return ActiveRecordListType
   */
  public static function listOf($class)
  {
    Yii::debug(static::class);
    return new ActiveRecordValuesListType(static::getInstance($class));
  }
}
