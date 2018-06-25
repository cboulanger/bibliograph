<?php

namespace app\modules\webservices;
use app\modules\webservices\models\Record;
use app\modules\webservices\repositories\IConnector;
use Iterator;
use lib\cql\Prefixable;
use yii\base\BaseObject;

/**
 * Class AbstractConnector
 * @package modules\webservices
 * @property string $id
 * @property string $name
 * @property string $description
 * @property array $indexes
 */
abstract class AbstractConnector extends BaseObject //implements IConnector // throws!
{
  /**
   * @var string
   */
  protected $id = "";

  /**
   * @var string
   */
  protected $name = "";

  /**
   * @var string
   */
  protected $description = "";

  /**
   * @var array
   */
  protected $indexes = [];

  /**
   * @return string
   */
  public function getId(){
    return $this->id;
  }

  /**
   * @return string
   */
  public function getName(){
    return $this->name;
  }

  /**
   * @return string
   */
  public function getDescription(){
    return $this->description;
  }

  /**
   * @return array
   */
  public function getIndexes(){
    return $this->indexes;
  }

  /**
   * Queries
   * @param Prefixable $cql
   * @return int
   */
  public abstract function search(Prefixable $cql): int;

  /**
   * Generator function that yields Record instances
   * @return Iterator|Record
   */
  public abstract function recordIterator(): Iterator;
}