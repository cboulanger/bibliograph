<?php

namespace app\controllers\traits;
use ReflectionClass;
use ReflectionProperty;
use Yii;
use Yii\base\UnknownPropertyException;

/**
 * Trait PropertyPersistenceTrait
 * Persists the properties of the given class instance. Currently works only
 * on the class level, i.e. all class instances will have the same properties
 * persisted.
 * @package app\controllers\traits
 */
trait PropertyPersistenceTrait
{

  /**
   * Returns a id for caching setup data which needs to be unique and always identical.
   * Currently an MD5 hash of the class name
   * @return string
   */
  protected function getPropertyCacheId() {
    return md5(get_called_class());
  }

  /**
   * Restore cached properties
   * @return array
   */
  protected function restoreProperties()
  {
    $properties = unserialize(Yii::$app->cache->get($this->getPropertyCacheId()));
    if (is_array($properties)) {
      //Yii::debug(">>> Restoring properties", __METHOD__);
      //Yii::debug($properties);
      foreach ($properties as $property => $value) {
        try {
          $this->$property = $value;
        } catch (UnknownPropertyException $e) {
          Yii::error($e);
        }
      }
      return $properties;
    }
    //Yii::debug(">>> No properties saved yet", __METHOD__);
    return [];
  }

  /**
   * Save all properties of this instance to a file cache which are
   * scalar values or arrays of scalar values
   * @param array $keys If given, only save the properties in this array
   */
  protected function saveProperties(array $keys=[])
  {
    function is_serializable($value) {
      if (is_object($value)) {
        if (get_class($value) === "stdClass") {
          $value = (array)$value;
        } else {
          return false;
        }
      }
      if (is_array($value)) {
         return array_reduce($value, function($carry, $item){
          return $carry && is_serializable($item);
        }, true);
      }
      return is_scalar($value);
    }
    $objectProperties = get_object_vars($this);
    $keys = count($keys) ? $keys : array_keys($objectProperties);
    $savedProperties = [];
    foreach ($keys as $key) {
      $value = $objectProperties[$key];
      if (is_serializable($value)) {
        $savedProperties[$key] = $value;
      }
    }
    //Yii::debug(">>> Saving properties", __METHOD__);
    Yii::$app->cache->set($this->getPropertyCacheId(), serialize($savedProperties));
  }

  /**
   * Saves only the properties of the calling class, not of its parents
   */
  protected function saveOwnProperties() {
    $properties = (new ReflectionClass(self::class))->getProperties();
    $ownPropertyNames = array_map(
      function(ReflectionProperty $property) {
        return $property->getName();
      },
      array_filter($properties, function(ReflectionProperty $property) {
        return $property->getDeclaringClass() == get_called_class();
      })
    );
    $this->saveProperties($ownPropertyNames);
  }

  /**
   * Reset file cache
   */
  protected function resetSavedProperties()
  {
    Yii::debug("Resetting saved properties", self::CATEGORY);
    Yii::$app->cache->delete($this->getPropertyCacheId());
  }
}
