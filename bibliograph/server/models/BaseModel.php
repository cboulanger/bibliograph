<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2017 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Datasource;

class BaseModel extends ActiveRecord
{
    /**
     * The name of the datasource the model is attached to.
     * the "datasource" in bibliograph parlance refers to a named collection
     * of models within a database
     */
    public static $datasource = null;

    /**
     * Returns the database object used by the model
     * @return \yii\db\Connection
     */
    public static function getDb()
    {
        if( self::$datasource ){
          return Datasource::getInstanceFor( self::$datasource )->getConnection();
        }
        return parent::getDb();
    }

    /**
     * Sets the datasource that all models based on the class will use. If you use several 
     * instances of the same class, you need to set the datasource explicitly before each
     * query, since the datasource is a static property of the class. 
     * MyClass::setDatasource("datasource")::find()->...
     * @return string The name of the called class.
     */
    public static function setDatasource($datasourceName)
    {
      if( empty($datasourceName) or ! is_string($datasourceName) ) throw new \InvalidArgumentException("Invalid Datasource name");
      self::$datasource = $datasourceName;
      return \get_called_class();
    }

    /**
     * Gets the name of  the datasource that the model belongs to
     * @return string The name of the datasource
     */
    public static function getDatasource()
    {
      return self::$datasource;
    }

    /**
     * Shim method
     *
     * @param [type] $string
     * @return void
     */
    protected function tr($string)
    {
        return Yii::t('app', $string );
    }
}
