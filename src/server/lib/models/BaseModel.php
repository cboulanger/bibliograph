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
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

use app\models\Datasource;


/**
 * @property array $formData
 *    A associative array of arrays containing data for the
 *    dialog.Form widget
 */
class BaseModel extends ActiveRecord
{

  /**
   * @todo generic table name algorithm
   * @return string
   */
//  static function tableName()
//  {
//    return basename( static::class ) ...
//  }


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
        'class'               => TimestampBehavior::class,
        'createdAtAttribute'  => 'created',
        'updatedAtAttribute'  => 'modified',
        'value'               => new Expression('NOW()'),
      ],
    ];
  }

  //-------------------------------------------------------------
  // Virtual properties
  //-------------------------------------------------------------

  /**
   * Data for a \lib\dialog\Form in which the
   * model data can be edited or null if the model cannot be edited
   * @return array|null
   */
  public function getFormData(){
    return null;
  }

  //-------------------------------------------------------------
  // Datasource feature
  //-------------------------------------------------------------

  /**
   * The Datasource instance to which the model is attached
   * and which provides the database connection for all attached models
   * the "datasource" in bibliograph parlance refers to a named collection
   * of models within a database
   * @var Datasource
   */
  protected static $datasource = null;

  /**
   * Flag to prevent infinite recursion
   * @var bool
   */
  protected static $__lookingUpDatasource = null;

  /**
   * Returns the database object used by the model
   * @return \yii\db\Connection
   * @throws \Exception
   */
  public static function getDb()
  {
    if(static::$__lookingUpDatasource ){
      throw new \RuntimeException("Please instantiate datasource first");
    }
    if( static::$datasource ){
      $db = static::$datasource->getConnection();
    } else {
      $db = parent::getDb();
    }
    //Yii::debug(">>>>>>>>>>>>> " . static::class . " :  " . $db->dsn);
    return $db;
  }

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
    static :: $datasource = $datasource;
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
    return static::findOne( ['namedId' => $namedId ] );
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
    $exception =  new \yii\db\Exception("Error saving model " . get_class($this));
    $exception->errorInfo = $this->getFirstErrors();
    Yii::error($this->getFirstErrors());
    throw $exception;
  }

  /**
   * overridden to forward the event to the application object so that
   * anonymous listeners can listen to it.
   */
  public function afterDelete()
  {
    Yii::$app->trigger( BaseActiveRecord::EVENT_AFTER_DELETE, new ModelEvent([
      'sender' => $this
    ]));
    parent::afterDelete();
  }
}
