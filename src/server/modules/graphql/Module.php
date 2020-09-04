<?php

namespace app\modules\graphql;

//use GraphQL\Type\Definition\ObjectType;
//use GraphQL\Type\Definition\Type;
//use yii\graphql\GraphQLModuleTrait;
//use GraphQL\Type\Schema;
//use GraphQL\GraphQL;

class Module extends \lib\Module {

  /**
   * @inheritdoc
   */
  public $controllerNamespace =  __NAMESPACE__;

  /**
   * @inheritDoc
   * @return bool|void
   */
  public function install($enabled = false){
    parent::install(true);
  }
}
