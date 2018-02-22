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

namespace lib\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

use app\models\Datasource;

class BaseModel extends ActiveRecord
{

  //-------------------------------------------------------------
  // Behaviors
  //-------------------------------------------------------------

  /**
   * Class behaviors. Adds a timestamp to the `created` and `modified` columns
   * @return array
   */
  public function behaviors()
  {
    return [
      [
        'class'               => TimestampBehavior::className(),
        'createdAtAttribute'  => 'created',
        'updatedAtAttribute'  => 'modified',
        'value'               => new Expression('NOW()'),
      ],
    ];
  }

  //-------------------------------------------------------------
  // Abstract methods
  //-------------------------------------------------------------

  /**
   * Returns data for a \lib\dialog\Form in which the
   * model data can be edited
   * @return array|null
   */
  public function formData(){
    return null;
  }

  //-------------------------------------------------------------
  // Datasource feature
  //-------------------------------------------------------------

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
    if( static :: $datasource ){
      return Datasource::getInstanceFor( static::$datasource )->getConnection();
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
    static :: $datasource = $datasourceName;
    return \get_called_class();
  }

  /**
   * Gets the name of  the datasource that the model belongs to
   * @return string The name of the datasource
   */
  public static function getDatasource()
  {
    return static :: $datasource;
  }

  //-------------------------------------------------------------
  // Shorthand methods
  //-------------------------------------------------------------  

  /**
   * Shorthand method to find ActiveRecord with the given named id
   *
   * @param string $namedId
   * @return \lib\models\BaseModel
   */
  public static function findByNamedId( $namedId )
  {
    return static :: findOne( ['namedId' => $namedId ] );
  }

  //-------------------------------------------------------------
  // Overridden methods
  //-------------------------------------------------------------  

  /**
   * Overridden to log validation errors
   *
   * @param boolean $runValidation
   * @param array|null $attributeNames
   * @return boolean
   * @throws \yii\db\Exception
   */
  public function save( $runValidation = true, $attributeNames = null )
  {
    if( parent::save( $runValidation, $attributeNames ) ){
      return true;
    }
    Yii::error("Error saving model " . get_class($this) );
    Yii::error( $this->getFirstErrors() );
    //Yii::warning( $this->getErrorSummary() );
    //return false;
    throw new \yii\db\Exception("Error saving model.");
  }
}
