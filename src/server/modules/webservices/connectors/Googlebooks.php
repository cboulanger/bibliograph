<?php

namespace app\modules\webservices\connectors;

use app\modules\webservices\IConnector;
use app\modules\webservices\InvalidIndexException;
use app\modules\webservices\models\Record;
use ADCI\FullNameParser\Parser;
use Illuminate\Support\Str;
use Iterator;
use lib\cql\Prefixable;
use app\modules\webservices\AbstractConnector;
use lib\cql\Triple;
use lib\exceptions\UserErrorException;
use Yii;

/**
 * Google Books Connector
 * @see https://developers.google.com/books/docs/v1/using
 * @package app\modules\webservices\connectors
 */
class Googlebooks extends AbstractConnector implements IConnector
{
  /**
   * @inheritdoc
   */
  protected $id = "googlebooks";

  /**
   * @inheritdoc
   */
  protected $name = "Google Books Connector";

  /**
   * @inheritdoc
   */
  protected $description = "Web service to look up book information, based on Google Books API v1.";

  /**
   * @inheritdoc
   */
  protected $indexes = ['isbn', 'lccn', 'oclc', 'inpublisher', 'inauthor', 'intitle', 'subject'];

  private $data = null;

  private $map = null;

  public function __construct($config = [])
  {
    parent::__construct($config);
    $this->map = $this->createMap();
  }

  private function createMap() {
    $parser = new Parser();
    return [
      "title" => "title",
      "authors" => ["author", function($arr) use ($parser){
        return implode("; ", array_map(function($item) use ($parser){
          $parsedName = $parser->parse($item);
          return $parsedName->getLastName() . ", " . $parsedName->getFirstName() . " " . $parsedName->getMiddleName();
        }, $arr));
      }],
      "description" => "abstract",
      "industryIdentifiers" => ["isbn", function($arr) {
        return array_reduce($arr, function($carry, $item) {
          return $item['type'] === "ISBN_13" ? $item['identifier'] : $carry;
        });
      }],
      "pageCount" => "pages",
      "categories" => ["keywords", function($arr) {
        return implode("; ", $arr);
      }],
      "language" => "language",
      "publisher" => "publisher",
      "publishedDate" => ["year", function($date) {
        return date('Y', strtotime($date));
      }],
      "previewLink" => "url"
    ];
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
    $index = $cql->index->value;
    if (!in_array($index, $this->indexes)) {
      throw new InvalidIndexException("Invalid index $index");
    }
    if (!$index and Str::startsWith($cql->term->value, "978")) {
      $index = "isbn";
    }
    switch ($index) {
      case "isbn":
        $term = str_replace("-","", $cql->term->value);
        break;
      default:
        $term = $cql->term->value;
    }
    if ($index) {
      $url = "https://www.googleapis.com/books/v1/volumes?q=$index:$term";
    } else {
      $url = "https://www.googleapis.com/books/v1/volumes?q=$term";
    }
    try {
      $this->data = json_decode(file_get_contents($url),true);
    } catch (\Throwable $e) {
      throw new UserErrorException("Error requesting data from Google: " . $e->getMessage());
    }
    $this->hits = $this->data['totalItems'];
    return $this->hits;
  }

  /**
   * Generator method that yields a Record object
   * @return Iterator|Record
   */
  public function recordIterator() : Iterator {
    foreach ($this->data["items"] as $item) {
      $record = new Record();
      $record->reftype = "book";
      foreach($item['volumeInfo'] as $sourceProperty => $value) {
        if (isset($this->map[$sourceProperty])) {
          $targetProperty = $this->map[$sourceProperty];
          if (is_array($targetProperty)) {
            $value = $targetProperty[1]($value);
            $targetProperty = $targetProperty[0];
          }
          $record->$targetProperty = is_array($value) ? implode("", $value) : (string) $value;
        }
      }
      yield $record;
    }
  }
}

