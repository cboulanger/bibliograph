<?php

namespace app\modules\webservices\repositories;

use app\models\Reference;
use lib\cql\Prefixable;

interface IConnector
{
  /**
   * Queries
   * @param Prefixable $cql
   * @return Reference[]
   */
  public function query( Prefixable $cql ) : array;

  /**
   * Used to transform query before it is converted to a CQL object
   * @param string $query
   * @return string
   */
  public function fixQuery( string $query ): string;
}