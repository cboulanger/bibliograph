<?php

namespace app\modules\webservices\connectors;

use app\models\Reference;
use app\modules\webservices\AbstractConnector;
use app\modules\webservices\models\Record;
use app\modules\webservices\Module;
use Illuminate\Support\Arr;
use lib\cql\Prefixable;
use lib\cql\SearchClause;
use lib\cql\Triple;
use RenanBr\CrossRefClient;
use voku\cache\CachePsr16;
use Yii;
use yii\helpers\ArrayHelper;
use yii\validators\StringValidator;

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
  protected $name = "CrossRef (DOI only)";

  /**
   * @inheritdoc
   */
  protected $description = "CrossRef metadata repository. See https://www.crossref.org";

  /**
   * @inheritdoc
   */
  protected $indexes = ['doi'];

  /**
   * @var Reference[]
   */
  private $records = [];

  /**
   * @inheritdoc
   */
  public function search(Prefixable $cql): int
  {

    if ($cql instanceof Triple) {
      throw new \InvalidArgumentException("Triple not implemented.");
    }
    /** @var SearchClause $searchClause */
    $searchClause = $cql;
    $searchTerm = $searchClause->term->value;
    switch ($searchClause->index->value) {
      case "doi":
        $path = "works/$searchTerm";
        break;
      default:
        throw new \InvalidArgumentException(Yii::t(Module::CATEGORY, "'{field} is not a valid search field", [
          'field' => $searchClause->index->value
        ]));
    }
    $client = new CrossRefClient();
    $client->setCache(new CachePsr16());
    $client->setUserAgent('Bibliograph/3.x (http://www.bibliograph.org; mailto:info@bibliograph.org)');
    $result = $client->request($path);
    //Yii::debug($result);
    $data = $result['message'] ?? null;
    //Yii::debug($data);
    if( ! is_array($data) ) return 0;
    $map = [
      'reftype'     => 'type',
      'year'        => ['issued','date-parts'],
      'url'         => 'URL',
      'isbn'        => 'ISBN',
      'subtitle'    => 'subtitle',
      'author'      => 'author',
      'editor'      => 'editor',
      'pages'       => 'page',
      'doi'         => 'DOI',
      'publisher'   => 'publisher',
      'address'     => 'publisher-location',
      'title'       => 'title',
      'volume'      => 'volume',
      'number'      =>  'issue',
      'language'    => 'language',
      'journal'     => 'container-title',
      'booktitle'   => 'container-title',
      'abstract'    => 'abstract'
    ];
    $r=[];
    $quality=0;
    $record = new Record();
    foreach ($map as $attribute => $path) {
      $value = ArrayHelper::getValue($data,$path);
      if( ! $value ) continue;
      switch ($attribute){
        case 'abstract':
          $value = strip_tags($value);
          break;
        case 'reftype':
          $value = [
              'book' => isset($data['editor']) ? 'collection' : 'book',
              'book-chapter' => 'inbook',
              'journal-article' => 'article'
            ][$value] ?? 'article';
          break;
        case 'year':
          $value = (string) $value[0][0];
          break;
        case "isbn":
          $value = implode("; ", array_slice( (array) $value, 0,2));
          break;
        case 'title':
        case 'subtitle':
        case 'journal':
        case 'booktitle':
          $value = implode(". ", (array) $value);
          break;
        case 'author':
        case 'editor':
          $value = implode("; ", array_map(function($person){
            return
              $this->uppercase_first($person['family'] ?? "??")
              . ", "
              . $this->uppercase_first( $person['given'] ?? "??");
          }, (array) $value));
          break;
        default:
          if( ! is_scalar($value) ) {
            $value = json_encode($value);
          }
      }
      $validators = $record->getActiveValidators($attribute);
      foreach ($validators as $validator) {
        if( $validator instanceof StringValidator and $validator->max ){
          $data[$attribute] = substr($value, 0, $validator->max);
        }
      }
      $record->$attribute = $value;
      $quality++;
    }
    $record->quality = $quality;
    $this->records[] = $record;
    return 1;
  }

  protected function uppercase_first( $string ){
    return
      mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
      //. mb_strtolower( substr($string,1) );
  }

  /**
   * @inheritdoc
   */
  public function recordIterator(): \Iterator
  {
    foreach ($this->records as $record) {
      yield $record;
    }
  }
}