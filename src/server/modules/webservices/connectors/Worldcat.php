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
  /**
   * @inheritdoc
   */
  protected $id = "worldcat";

  /**
   * @inheritdoc
   */
  protected $name = "WorldCat (ISBN only)";

  /**
   * @inheritdoc
   */
  protected $description = "Web service to look up book-related metadata, based on WorldCat information.";

  /**
   * @inheritdoc
   */
  protected $indexes = ['isbn'];

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
    $map = [
      'year'      => 'datePublished',
      'contents'  => 'description',
      'language'  => 'inLanguage',
      'keywords'  => 'keywords',
      'title'     => 'name'
    ];
    foreach ($this->manifestations as $id => $m) {
      $data = [
        'reftype'   => 'book',
        'quality'   => 0,
      ];
      foreach ( $map as $recordKey => $worldCatKey ){
        if( ! $m->$worldCatKey ) continue;
        if( $m->$worldCatKey instanceof Entity ){
          $data[$recordKey] = $m->$worldCatKey->name;
        } elseif( is_scalar( $m->$worldCatKey ) ){
          $data[$recordKey] = $m->$worldCatKey;
        } else {
          continue;
        }
        $data['quality']++;
      }
      if ( $m->bookEdition ) {
        $data['edition'] = $m->bookEdition;
        $data['quality']++;
      }
      if (count( $m->getIsbns()) ){
        $data['isbn'] = implode("; ", array_slice( $m->getIsbns(), 0,2));
        $data['quality']++;
      }
      $authors = [];
      $editors = [];
      $translators = [];
      foreach (['creator','author','contributor','editor','translator'] as $prop){
        if( ! $m->$prop ) continue;
        $list = is_array( $m->$prop ) ? $m->$prop : [ $m->$prop ];
        foreach ($list as $entity){
          if( $entity instanceof Entity ) {
            if( $entity->familyName and $entity->givenName ){
              $name = implode(" ",(array)$entity->familyName) . ", " . implode(" ", (array) $entity->givenName);
              $data['quality'] += 5;
            } elseif( $entity->name ){
              $name = $entity->name;
              $data['quality']++;
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
                $data['quality']++;
                break;
              case 'translator':
                $translators[] = $name;
                $data['quality']++;
                break;
            }
          }
        }
      }
      if (count($authors)) $data['author'] = implode("; ", $authors);
      if (count($editors)) $data['editor'] = implode("; ", $editors);
      $data['translator'] = implode("; ", $translators);
      if( $m->publisher instanceof Entity ){
        $data['publisher'] = $m->publisher->name;
        $data['quality']++;
        $location = $m->publisher->location;
        if( $location instanceof Entity) {
          $data['address'] = $location->name;
          $data['quality']++;
        } elseif (is_string( $location) ){
          $data['address'] = $location;
          $data['quality']++;
        }
      }
      yield new Record($data);
    }
  }
}

