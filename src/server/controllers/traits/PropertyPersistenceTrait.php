<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 15.06.18
 * Time: 08:26
 */

namespace app\controllers\traits;
use Yii;

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
      //Yii::debug(array_keys($properties));
      foreach ($properties as $property => $value) {
        $this->$property = $value;
      }
      return $properties;
    }
    //Yii::debug(">>> No properties saved yet", __METHOD__);
    return [];
  }

  /**
   * Save all properties of this instance to a file cache which are
   * scalar values or arrays of scalar values
   */
  protected function saveProperties()
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
    $properties = [];
    foreach( get_object_vars($this) as $property => $value) {
      if (is_serializable($value)) {
        $properties[$property] = $value;
      }
    }
    //Yii::debug(">>> Saving properties", __METHOD__);
    Yii::$app->cache->set($this->getPropertyCacheId(), serialize($properties));
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
