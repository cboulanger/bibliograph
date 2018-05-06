<?php

namespace app\modules\webservices\connectors;

use app\models\Reference;
use app\modules\converters\import\BibtexUtf8;
use app\modules\webservices\Module;
use lib\cql\Prefixable;
use lib\cql\SearchClause;
use lib\cql\Triple;
use RenanBr\CrossRefClient;
use voku\cache\CachePsr16;
use Yii;
use yii\helpers\Url;

/**
 * Class Crossref Connector
 * @see https://github.com/renanbr/crossref-client
 * @see https://github.com/CrossRef/rest-api-doc
 * @package app\modules\webservices\repositories
 */
class Crossref
{

  public $id = "crossref";

  public $name = "CrossRef metadata repository";

  public $description = "see https://www.crossref.org";

  /**
   * This translates a CQL query object into a query the webservice can understand,
   * launches the query and parses the result into an array of Reference objects
   * @param Prefixable $cql
   * @return Reference[]
   */
  public function query( Prefixable $cql ) : array {

    if( $cql instanceof Triple ){
      throw new \InvalidArgumentException("Triple not implemented.");
    }
    /** @var SearchClause $searchClause */
    $searchClause = $cql;
    $searchTerm = $searchClause->term;
    $params=[];
    switch ($searchClause->index) {
      case "doi":
        $path = "works/$searchTerm/transform/application/x-bibtex";
        break;
      case 'isbn':
        $path = "works/transform/application/x-bibtex?filter=isbn:$searchTerm";
        break;
      default:
      throw new \InvalidArgumentException(Yii::t(Module::CATEGORY, "'{field} is not a valid search field",[
        'field' => $searchClause->index
      ]));
    }
    $client = new CrossRefClient();
    $client->setCache(new CachePsr16());
    $client->setUserAgent('Bibliograph/3.x (http://www.bibliograph.org; mailto:info@bibliograph.org)');
    $bibtex = $client->request( $path );
    $references = (new BibtexUtf8())->parse($bibtex);
    return $references;
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
    } if (substr($query, 0, 3) == "10.") {
      $query = 'doi=' . $query;
    }
    return $query;
  }
}