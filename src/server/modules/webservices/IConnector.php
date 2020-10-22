<?php

namespace app\modules\webservices;

use app\models\Reference;
use app\modules\webservices\models\Record;
use Iterator;
use lib\cql\Prefixable;

interface IConnector
{
  /**
   * @return string
   */
  public function getId();

  /**
   * @return string
   */
  public function getName();

  /**
   * @return string
   */
  public function getDescription();

  /**
   * Returns an array of indexes that can be searched
   * @return array
   */
  public function getIndexes();

  /**
   * Get the number of hits of the last search
   * @return int
   */
  public function getHits();

  /**
   * Queries the data repository and returns the number of results
   * @param Prefixable $cql
   * @return int
   */
  public function search( Prefixable $cql ) : int;

  /**
   * Generator function that yields Record instances
   * @return Iterator|Record
   */
  public function recordIterator() : Iterator;

}
