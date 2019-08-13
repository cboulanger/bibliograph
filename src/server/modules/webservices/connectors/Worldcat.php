<?php

namespace app\modules\webservices\connectors;

use app\modules\webservices\IConnector;
use app\modules\webservices\models\Record;
use app\modules\webservices\Module;
use app\modules\webservices\RecordNotFoundException;
use Iterator;
use lib\cql\Prefixable;
use lib\cql\SearchClause;
use lib\cql\Triple;
use app\modules\webservices\AbstractConnector;
use WorldCatLD\Entity;
use WorldCatLD\Manifestation;
use Yii;
use yii\validators\StringValidator;

/**

 * WorldCat Linkeddata connector
 * @see https://github.com/rsinger/worldcat-linkeddata-php
 * @see http://schema.org/Book
 * @package app\modules\webservices\connectors

 */
class Worldcat extends AbstractConnector implements IConnector
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
   * @var null
   */
  private $isbn = null;

  /**
   *
   * @param Prefixable $cql
   * @return Manifestation[]
   * @throws \Throwable
   */
  protected function createManifestations( Prefixable $cql, $retry=0 ) : array {
    if( $cql instanceof Triple ){
      throw new \InvalidArgumentException("Triple not implemented.");
    }
    /** @var SearchClause $searchClause */
    $searchClause = $cql;
    $searchTerm = $searchClause->term->value;
    $manifestation = new Manifestation();
    $this->isbn = null;
    switch ($searchClause->index->value) {
      case 'isbn':
        try {
          $this->isbn = $searchTerm;
          $manifestation->findByIsbn($searchTerm);
          return $manifestation->getWork()->getWorkExample();
        } catch( \GuzzleHttp\Exception\ServerException $e ){
          if( $retry > 3 ){
            throw new RecordNotFoundException(
              Yii::t(
                Module::CATEGORY,
                "Server error trying to retrieve information on ISBN '{isbn}' (Tried 3 times)",
                [ 'isbn' => $searchTerm ]
              )
            );
          }
          // try again
          sleep(2);
          Yii::debug("Server error, retrying...",Module::CATEGORY, __METHOD__);
          return $this->createManifestations($cql, $retry+1);
        } catch( \Throwable $e){
          if( $e instanceof \WorldCatLD\exceptions\ResourceNotFoundException
           or $e instanceof \GuzzleHttp\Exception\ClientException ){
            throw new RecordNotFoundException(
              Yii::t(
                Module::CATEGORY,
                "Could not find information for ISBN '{isbn}'",
                [ 'isbn' => $searchTerm ]
              )
            );
          }
          throw $e;
        }
        break;
    }
    throw new \InvalidArgumentException(
      Yii::t(
        Module::CATEGORY,
        "'{field} is not a valid search field",
        [ 'field' => $searchClause->index->value ]
      )
    );
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
      $record = new Record();
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
        // since we retrieve many manifestations of the item, we need to priviledge the ones
        // that have the ISBN that we were looking for originally
        if( $this->isbn and in_array($this->isbn, $m->getIsbns()) ){
          $data['quality'] += 10;
        } else {
          $data['quality']++;
        }
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
              $name = implode( ", ", (array) $entity->name);
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
      if (count($translators)) $data['translator'] = implode("; ", $translators);
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
      foreach ($data as $attribute => $value) {
        $validators = $record->getActiveValidators($attribute);
        foreach ($validators as $validator) {
          if( $validator instanceof StringValidator and $validator->max ){
            $data[$attribute] = substr($value, 0, $validator->max);
          }
        }
      }
      $record->setAttributes($data);
      yield $record;
    }
  }
}

