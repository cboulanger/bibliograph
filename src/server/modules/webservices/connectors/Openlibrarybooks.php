<?php

namespace app\modules\webservices\connectors;

use ADCI\FullNameParser\Parser;
use app\models\Reference;
use app\modules\webservices\AbstractConnector;
use app\modules\webservices\models\Record;
use app\modules\webservices\IConnector;
use lib\cql\Prefixable;
use lib\cql\Triple;
use lib\exceptions\UserErrorException;


/**
 * Open Library Books Connector
 * @see https://openlibrary.org/dev/docs/api/books
 *
 * todo: For name parsing, switch from adci/full-name-parser to https://github.com/theiconic/name-parser once we drop PHP 7.0 support
 * @package app\modules\webservices\connectors
 */
class Openlibrarybooks extends AbstractConnector implements IConnector
{

  /**
   * @inheritdoc
   */
  protected $id = "openlibrarybooks";

  /**
   * @inheritdoc
   */
  protected $name = "Open Library Books";

  /**
   * @inheritdoc
   */
  protected $description = "The Open Library Books API provides a programmatic client-side method for querying information of books using Javascript. See https://openlibrary.org/dev/docs/api/books";

  /**
   * @inheritdoc
   */
  protected $indexes = ['isbn', 'oclc','lccn','olid'];

  /**
   * @var Reference[]
   */
  private $records = [];

  private $map;

  public function __construct($config = [])
  {
    parent::__construct($config);
    $this->records = [];
    $this->map = $this->createMap();
  }

  private function createMap() {
    $parser = new Parser();
    return [
      "abstract" => "description",
      "authors" => ["author", function($arr) use ($parser){
        return implode("; ", array_map(function($item) use ($parser){
          $parsedName = $parser->parse($item['name']);
          return $parsedName->getLastName() . ", " . $parsedName->getFirstName() . " " . $parsedName->getMiddleName();
        }, $arr));
      }],
      "description" => "abstract",
      "isbn_13" => ["isbn", function($arr) {
        return implode("; ", $arr);
      }],
      "language" => ["language", function($arr) {
        return implode("; ", array_map(function($item) {
          return array_slice(explode("/", $item['key']), -1)[0];
        }, $arr));
      }],
      "notes" => "note",
      "number_of_pages" => "pages",
      "publishers" => ["publisher", function($arr) {
        return implode("; ", $arr);
      }],
      "publish_date" => ["year", function($date) {
        return date('Y', strtotime($date));
      }],
      "publish_places" => ["address", function($arr) {
        return implode("; ", $arr);
      }],
      "subject" => ["keywords", function($arr) {
        return implode("; ", $arr);
      }],
      "table_of_contents" => ["contents", function($arr) {
        return implode("\n", array_map(function($item) {
          return $item['title'];
        }, $arr));
      }],
      "title" => "title",
      "type" => ["reftype", function($obj) {
        switch ($obj["key"]) {
//          case "/type/edition":
//            return "collection";
          default:
            return "book";
        }
      }]
    ];
  }

  /**
   * @inheritdoc
   */
  public function search(Prefixable $cql): int
  {
    if ($cql instanceof Triple) {
      throw new \InvalidArgumentException("Triple not implemented.");
    }
    $searchIndex = $cql->index->value;
    $searchTerm  = preg_replace("/[^0-9]/", "", $cql->term->value);
    $bibkeys = "$searchIndex:$searchTerm";
    $url = "https://openlibrary.org/api/books?bibkeys=$bibkeys&jscmd=details&format=json";
    try {
      $response = json_decode(file_get_contents($url), true);
    } catch (\Exception $exception) {
      throw new UserErrorException($exception->getMessage());
    }
    if (! is_array($response) or !count($response)) return 0;
    $data = $response[$bibkeys];
    if (! is_array($data) or !count($data)) return 0;
    $record = new Record();
    $record->type = "book";
    foreach($data["details"] as $sourceProperty => $value) {
      if (isset($this->map[$sourceProperty])) {
        $targetProperty = $this->map[$sourceProperty];
        if (is_array($targetProperty)) {
          $value = $targetProperty[1]($value);
          $targetProperty = $targetProperty[0];
        }
        $record->$targetProperty = is_array($value) ? implode("", $value) : (string) $value;
      }
    }
    $this->records = [$record];
    $this->hits = count($this->records);
    return $this->hits;
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
