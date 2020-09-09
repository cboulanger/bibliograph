<?php

namespace app\modules\graphql;

use GraphQL\Type\Definition\ObjectType;

/**
 *
 */
class ActiveRecordQuery extends ObjectType {

  /**
   * ModelProxyQuery constructor.
   * @param array $config An array mapping field names to ModelProxyType instances
   */
  function __construct(array $config) {
    parent::__construct([
      'fields' => $this->createFields($config)
    ]);
  }

  /**
   * @param array $config
   * @return array
   */
  private function createFields(array $config) {
    $fields = [];
    /** @var ActiveRecordType $type */
    foreach ($config as $name => $type) {
      $fields[$name] = [
        'type' => $type,
        'args' => $type->getArgs(),
        'resolve' => [$type, "resolve"]
      ];
    }
    return $fields;
  }
}
