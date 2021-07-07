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

use Illuminate\Support\Str;
use lib\channel\BroadcastEvent;
use lib\channel\MessageEvent;
use Yii;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

use app\models\Datasource;


/**
 * @property integer $id
 * @property string $created
 * @property string $modified
 * @property array $formData
 *    A associative array of arrays containing data for the
 *    dialog.Form widget
 */
class BaseModel
  extends ActiveRecord
  implements IHasDatasource
{

  use ModelDatasourceTrait;

  /**
   * @var bool Whether to dispatch client or broadcast
   * events when the model changes
   */
  public $dipatchChangeMessages = true;

  /**
   * @return string
   * @todo generic table name algorithm
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
        'class' => TimestampBehavior::class,
        'createdAtAttribute' => 'created',
        'updatedAtAttribute' => 'modified',
        'value' => new Expression('NOW()'),
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
  public function getFormData()
  {
    return null;
  }

  //-------------------------------------------------------------
  // Datasource feature
  //-------------------------------------------------------------


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
    if (static::$__lookingUpDatasource) {
      throw new \RuntimeException("Please instantiate datasource first");
    }
    if (static::$datasource) {
      $db = static::$datasource->getConnection();
    } else {
      $db = parent::getDb();
    }
    //Yii::debug(">>>>>>>>>>>>> " . static::class . " :  " . $db->dsn, __METHOD__);
    return $db;
  }


  //-------------------------------------------------------------
  // Shorthand methods
  //-------------------------------------------------------------

  /**
   * Shorthand method to find ActiveRecord with the given named id
   * Returns null if it doesn't exist.
   * @param string $namedId
   * @return \lib\models\BaseModel|null
   */
  public static function findByNamedId($namedId)
  {
    return static::findOne(['namedId' => $namedId]);
  }

  /**
   * Dispatches an change event as a message to the
   * authenticated client or to all connected clients
   *
   * @param MessageEvent|BroadcastEvent $event
   */
  public function dispatchChangeMessage(MessageEvent $event)
  {
    if ($this->dipatchChangeMessages) {
      Yii::$app->eventQueue->add($event);
    }
  }


  //-------------------------------------------------------------
  // Overridden methods
  //-------------------------------------------------------------

  /**
   * Fix values before model is saved
   *
   * @inheritdoc
   * @throws \yii\base\Exception
   */
  public function beforeSave($insert)
  {
    if (parent::beforeSave($insert)) {
      // add fixes here...
      return true;
    }
    return false;
  }

  /**
   * Overridden to log validation errors
   *
   * @param boolean $runValidation
   * @param array|null $attributeNames
   * @return boolean
   * @throws \yii\db\Exception
   */
  public function save($runValidation = true, $attributeNames = null)
  {
    if (parent::save($runValidation, $attributeNames)) {
      return true;
    }
    $message = sprintf("Saving model %s failed: %s", static::class, json_encode($this->getFirstErrors()));
    $exception = new \yii\db\Exception($message);
    $exception->errorInfo = $this->getFirstErrors();
    throw $exception;
  }

  /**
   * overridden to forward the event to the application object so that
   * anonymous listeners can listen to it.
   */
  public function afterDelete()
  {
    Yii::$app->trigger(BaseActiveRecord::EVENT_AFTER_DELETE, new ModelEvent([
      'sender' => $this
    ]));
    parent::afterDelete();
  }
}
