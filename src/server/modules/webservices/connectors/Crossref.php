<?php

namespace app\modules\webservices\repositories;

use app\models\Reference;
use lib\cql\Prefixable;
use lib\cql\Triple;
use lib\exceptions\UserErrorException;

class Crossref
{

  public $id = "crossref";

  public $name = "CrossRef metadata repository";

  public $description = "see https://www.crossref.org";

  /**
   * @param Prefixable $cql
   * @return Reference[]
   */
  public function query( Prefixable $cql ) : array {

    if( $cql instanceof Triple ){
      throw new \InvalidArgumentException("Triple not implemented.");
    }

    


  }


  /**
   * Used to transform query before it is converted to a CQL object
   * @param string $query
   * @return string
   */
  public function fixQuery(string $query) : string
  {
    // todo: identify DOI
    if (substr($query, 0, 3) == "978") {
      $query = 'isbn=' . $query;
    } elseif (!strstr($query, "=")) {
      $query = 'all="' . $query . '"';
    }
    return $query;
  }

}