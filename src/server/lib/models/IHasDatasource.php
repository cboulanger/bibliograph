<?php

namespace lib\models;

use app\models\Datasource;

interface IHasDatasource {

  /**
   * Sets the datasource that all models based on the class will use. If you use several
   * instances of the same class, you need to set the datasource explicitly before each
   * query, since the datasource is a static property of the class.
   * MyClass::setDatasource("datasource")::find()->...
   * @return string|Datasource $datasource The Datasource object or the namedId of the datasource
   */
  public static function setDatasource($datasource);

  /**
   * Gets the name of  the datasource that the model belongs to
   * @return Datasource
   */
  public static function getDatasource() : Datasource;
}
