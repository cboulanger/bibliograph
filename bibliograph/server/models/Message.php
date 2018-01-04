<?php

namespace app\models;

use Yii;
use yii\db\Expression;

use app\models\BaseModel;
use app\models\Session;

/**
 * This is the model class for table "data_Messages".
 *
 * @property integer $id
 * @property string $created
 * @property string $modified
 * @property string $name
 * @property resource $data
 * @property integer $SessionId
 */
class Message extends BaseModel
{
  /**
   * @inheritdoc
   */
  public static function tableName()
  {
    return 'data_Messages';
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['created', 'modified'], 'safe'],
      [['data'], 'string'],
      [['SessionId'], 'integer'],
      [['name'], 'string', 'max' => 100],
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    return [
      'id' => 'ID',
      'created' => 'Created',
      'modified' => 'Modified',
      'name' => 'Name',
      'data' => 'Data',
      'SessionId' => 'Session ID',
    ];
  }

  //-------------------------------------------------------------
  // Relations
  //-------------------------------------------------------------
  
  /**
   * Returns a yii\db\ActiveQuery to find the session object linked to 
   * the message instance 
   *
   * @return \yii\db\ActiveQuery
   */
  public function getSession()
  {
    return $this->hasOne(Session::className(), ['id' => 'SessionId']);
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Broadcast to all connected clients
   *
   * @param string|\app\controllers\sse\Channel $channel The name of the Channel or a Channel object
   * @param mixed $data Data, must be json_encode()able
   * @return void
   */
  public static function broadcast($channel, $data){
    $name = $channel instanceof \app\controllers\sse\Channel ? $channel->getName() : $channel; 
    foreach( Session::find()->all() as $session ){
      $message = new static([ 'name' => $name, 'data' => json_encode($data), 'SessionId' => $session->id ]);
      $message->save();
    }
  }

  /**
   * Send to the currently connected client only
   *
   * @param string|\app\controllers\sse\Channel $channel The name of the Channel or a Channel object
   * @param mixed $data Data, must be json_encode()able
   * @param string|null $sessionId If omitted, the Yii session id will be used
   * @return void
   */
  public static function send($channel, $data, $sessionId=null )
  {
    // @todo: validate channel name
    $name = $channel instanceof \app\controllers\sse\Channel ? $channel->getName() : $channel;     
    if( is_null( $sessionId ) ){
      $sessionId = Yii::$app->session->getId();
    }
    $session = Session::findOne([ 'namedId' => $sessionId ]);
    if( ! $session ){
      throw new \InvalidArgumentException("Session $sessionId does not exist.");
    }
    $message = new static([ 'name' => $name, 'data' => json_encode($data), 'SessionId' => $session->id ]);
    $message->save();
  }

  /**
   * Cleans up message queue by purging all entries that are older than
   * the given second interval.
   *
   * @param int $intervalInSeconds The interval in seconds. Defaults to 5 minutes
   * @return int Number of messages purged
   */
  public static function cleanup( $intervalInSeconds=60*5 )
  {
    $expression = new Expression('DATE_SUB(NOW(), INTERVAL :seconds SECOND)',['seconds' => $intervalInSeconds ]);
    return static::deleteAll(['<', 'modified', $expression]);
  }

}