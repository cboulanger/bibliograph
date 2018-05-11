<?php

namespace app\modules\webservices\repositories;

use app\models\Reference;
use app\modules\webservices\models\Record;
use Iterator;
use lib\cql\Prefixable;

interface IConnector
{

  /**
   * Queries
   * @param Prefixable $cql
   * @return int
   */
  public function search( Prefixable $cql ) : array;

  /**
   * Generator function that yields Record instances
   * @return Iterator|Record
   */
  public function recordIterator() : Iterator;

}