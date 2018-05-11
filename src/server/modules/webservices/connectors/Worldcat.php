<?php

namespace app\modules\webservices\connectors;

use app\modules\webservices\models\Record;
use app\modules\webservices\Module;
use Iterator;
use lib\cql\Prefixable;
use lib\cql\SearchClause;
use lib\cql\Triple;
use app\modules\webservices\AbstractConnector;
use WorldCatLD\Entity;
use WorldCatLD\Manifestation;
use Yii;

/**

 * WorldCat Linkeddata connector
 * @see https://github.com/rsinger/worldcat-linkeddata-php
 * @see http://schema.org/Book
 * @package app\modules\webservices\connectors

 */
class Worldcat extends AbstractConnector
{

  protected $id = "worldcat";

  protected $name = "WorldCat (ISBN only)";

  protected $description = "Web service to look up book-related metadata, based on WorldCat information.";

  protected $searchFields = ['isbn'];

  /**
   * @var Manifestation[]
   */
  private $manifestations = [];

  /**
   *
   * @param Prefixable $cql
   * @return Manifestation[]
   */
  protected function createManifestations( Prefixable $cql ) : array {
    if( $cql instanceof Triple ){
      throw new \InvalidArgumentException("Triple not implemented.");
    }
    /** @var SearchClause $searchClause */
    $searchClause = $cql;
    $searchTerm = $searchClause->term->value;
    $manifestation = new Manifestation();
    switch ($searchClause->index->value) {
      case 'isbn':
        $manifestation->findByIsbn($searchTerm);
        break;
      default:
        throw new \InvalidArgumentException(Yii::t(Module::CATEGORY, "'{field} is not a valid search field",[
          'field' => $searchClause->index->value
        ]));
    }
    return $manifestation->getWork()->getWorkExample();
  }


  /**
   * @param Prefixable $cql
   * @return  int number of records
   */
  public function search( Prefixable $cql ) : int
  {
    $this->manifestations = $this->createManifestations($cql);
    return count($this->manifestations);
  }

  /**
   * Generator method that yields a Record object
   * @return Iterator|Record
   */
  public function recordIterator() : Iterator {
    foreach ($this->manifestations as $id => $m) {
      $data = [
        'reftype'   => 'book',
        'edition'   => $m->bookEdition ?? null,
        'isbn'      => implode("; ", array_slice( $m->getIsbns(), 0,2)),
        'year'      => $m->datePublished,
        'contents'  => $m->description ?? null,
        'language'  => is_string($m->inLanguage) ? $m->inLanguage : $m->inLanguage->name,
        'keywords'  => $m->keywords,
        'title'     => $m->name
      ];
      $authors = [];
      $editors = [];
      $translators = [];
      foreach (['creator','author','contributor','editor','translator'] as $prop){
        if( ! $m->$prop ) continue;
        $list = is_array( $m->$prop ) ? $m->$prop : [ $m->$prop ];
        foreach ($list as $entity){
          if( $entity instanceof Entity ) {
            if( $entity->familyName and $entity->givenName ){
              $name = $entity->familyName . ", " . $entity->givenName;
            } elseif( $entity->name ){
              $name = $entity->name;
            } else {
              continue;
            }
            switch ($prop) {
              case 'creator':
              case 'author':
                // monograph
                $authors[] = $name;
                break;
              case 'contributor':
              case 'editor':
              // edited book
                $editors[] = $name;
                $data['reftype'] = 'collection';
                break;

              case 'translator':
                $translators[] = $name;
            }
          }
        }
      }
      if (count($authors)) $data['author'] = implode("; ", $authors);
      if (count($editors)) $data['editor'] = implode("; ", $editors);
      $data['translator'] = implode("; ", $translators);
      if( $m->publisher instanceof Entity ){
        $data['publisher'] = $m->publisher->name;
        $location = $m->publisher->location;
        if( $location instanceof Entity) {
          $data['address'] = $location->name;
        } elseif (is_string( $location) ){
          $data['address'] = $location;
        }
      }
      yield new Record($data);
    }
  }
}

