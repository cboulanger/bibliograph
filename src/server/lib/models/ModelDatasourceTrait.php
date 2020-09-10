<?php

namespace lib\models;

use app\models\Datasource;

trait ModelDatasourceTrait {

  /**
   * The Datasource instance to which the model is attached
   * and which provides the database connection for all attached models
   * the "datasource" in bibliograph parlance refers to a named collection
   * of models within a database
   * @var Datasource
   */
  protected static $datasource = null;

  /**
   * Sets the datasource that all models based on the class will use. If you use several
   * instances of the same class, you need to set the datasource explicitly before each
   * query, since the datasource is a static property of the class.
   * MyClass::setDatasource("datasource")::find()->...
   * @return string|Datasource $datasource The Datasource object or the namedId of the datasource
   */
  public static function setDatasource($datasource)
  {
    if (is_string($datasource) ){
      static::$__lookingUpDatasource = true;
      $datasource = Datasource::getInstanceFor($datasource);
      static::$__lookingUpDatasource = false;
    } elseif ( ! $datasource instanceof Datasource ){
      throw new \InvalidArgumentException("Passed object must be an instance of " . Datasource::class);
    }
    static::$datasource = $datasource;
    return \get_called_class();
  }

  /**
   * Gets the name of  the datasource that the model belongs to
   * @return Datasource
   */
  public static function getDatasource() : Datasource
  {
    return static::$datasource;
  }
}
