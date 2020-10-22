<?php

namespace app\modules\webservices\connectors;

use app\modules\webservices\IConnector;
use app\modules\webservices\InvalidIndexException;
use app\modules\webservices\models\Datasource;
use app\modules\webservices\models\Record;
use ADCI\FullNameParser\Parser;
use app\modules\webservices\Module;
use Illuminate\Support\Str;
use Iterator;
use lib\cql\Prefixable;
use app\modules\webservices\AbstractConnector;
use lib\cql\Triple;
use lib\exceptions\UserErrorException;
use Yii;

/**
 * A meta connector that searches all of the active search engines
 */
class All extends AbstractConnector implements IConnector
{
  /**
   * @inheritdoc
   */
  protected $id = "all";

  /**
   * @inheritdoc
   */
  protected $name = "Search all";

  /**
   * @inheritdoc
   */
  protected $description = "This connector allows to search through all of the registered webservices";

  /** @var AbstractConnector[]  */
  private $connectors = [];

  /**
   * @inheritDoc
   * @return array
   */
  public function getIndexes()
  {
     return array_reduce(
        Module::getInstance()->getConnectors(),
        function($indexes, $connector) {
          if ($connector->getId() === $this->id) {
            return $indexes;
          }
          return array_unique(array_merge($indexes, $connector->getIndexes()));
        },
        []
     );
  }

  /**
   * @param Prefixable $cql
   * @return  int number of records
   */
  public function search( Prefixable $cql ) : int
  {
    if( $cql instanceof Triple ){
      throw new \InvalidArgumentException("Complex queries are not yet implemented.");
    }
    $count = 0;
    foreach (Module::getInstance()->getConnectors() as &$connector) {
      if ($connector->getId() === $this->id) {
        continue;
      }
      try {
        $count += $connector->search($cql);
      } catch (InvalidIndexException $e) {
        Yii::debug($connector->getName() . ": " . $e->getMessage());
      }
    }
    return $count;
  }

  /**
   * Generator method that yields a Record object
   * @return Iterator|Record
   */
  public function recordIterator() : Iterator {
    foreach (Module::getInstance()->getConnectors() as &$connector) {
      if ($connector->getId() === $this->id or $connector->getHits() === 0) {
        continue;
      }
      $recordIterator = $connector->recordIterator();
      /** @var Record $record */
      foreach ($recordIterator as $record) {
        yield $record;
      }
    }
  }
}

