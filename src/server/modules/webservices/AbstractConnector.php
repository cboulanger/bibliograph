<?php

namespace app\modules\webservices;
use app\modules\webservices\models\Record;
use app\modules\webservices\IConnector;
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
abstract class AbstractConnector extends BaseObject implements IConnector
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
   * @var int
   */
  protected $hits = 0;

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
   * Get the number of hits of the last search
   * @return int
   */
  public function getHits(){
    return $this->hits;
  }

  /**
   * Queries the data repository and returns the number of results
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
