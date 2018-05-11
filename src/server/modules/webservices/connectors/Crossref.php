<?php

namespace app\modules\webservices\connectors;

use app\models\Reference;
use app\modules\converters\import\BibtexUtf8;
use app\modules\webservices\models\Record;
use app\modules\webservices\Module;
use app\modules\webservices\AbstractConnector;
use lib\cql\Prefixable;
use lib\cql\SearchClause;
use lib\cql\Triple;
use RenanBr\CrossRefClient;
use voku\cache\CachePsr16;
use Yii;

/**
 * Class Crossref Connector
 * @see https://github.com/renanbr/crossref-client
 * @see https://github.com/CrossRef/rest-api-doc
 * @package app\modules\webservices\connectors
 */
class Crossref extends AbstractConnector
{

  /**
   * @inheritdoc
   */
  protected $id = "crossref";

  /**
   * @inheritdoc
   */
  protected $name = "CrossRef metadata repository (DOI only)";

  /**
   * @inheritdoc
   */
  protected $description = "see https://www.crossref.org";

  /**
   * @inheritdoc
   */
  protected $searchFields = ['doi'];

  /**
   * @var Reference[]
   */
  private $references = [];

  /**
   * @inheritdoc
   */
  public function search( Prefixable $cql ) : int {

    if( $cql instanceof Triple ){
      throw new \InvalidArgumentException("Triple not implemented.");
    }
    /** @var SearchClause $searchClause */
    $searchClause = $cql;
    $searchTerm = $searchClause->term;
    switch ($searchClause->index->value) {
      case "doi":
        $path = "works/$searchTerm/transform/application/x-bibtex";
        break;
      default:
      throw new \InvalidArgumentException(Yii::t(Module::CATEGORY, "'{field} is not a valid search field",[
        'field' => $searchClause->index->value
      ]));
    }
    $client = new CrossRefClient();
    $client->setCache(new CachePsr16());
    $client->setUserAgent('Bibliograph/3.x (http://www.bibliograph.org; mailto:info@bibliograph.org)');
    $bibtex = $client->request( $path );
    Yii::debug($bibtex);

    //$this->references = (new BibtexUtf8())->parse($bibtex);
    return count($this->references);
  }

  /**
   * @inheritdoc
   */
  public function recordIterator() : \Iterator {
    foreach ($this->references as $reference){
      yield new Record(
        $reference->getAttributes( $reference->getAttributes() )
      );
    }
  }
}