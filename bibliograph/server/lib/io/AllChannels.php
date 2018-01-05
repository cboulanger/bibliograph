<?php

namespace lib\io;

use Yii;
use odannyc\Yii2SSE\SSEBase;

use app\models\Message;
use app\models\Session;

class AllChannels extends SSEBase
{

  /** 
   * The query for identifying messages 
   * @var \yii\db\ActiveQuery  
   */
  protected $query;

  /** 
   * The session object for identifying messages 
   * @var \app\models\Session
   */
  protected $session;  

  /**
   * Constructor
   *
   * @param string $name The name of the channel
   * @param string|null $sessionId The string id of the session. Defaults to
   * the Yii session id
   */
  public function __construct( $sessionId = null ){
    if( ! $sessionId ) {
      $sessionId = Yii::$app->session->getId();
    }
    $session = Session::findOne( [ 'namedId' => $sessionId ] );
    if( ! $session ) {
      throw new \InvalidArgumentException("Session '$sessionId' does not exist.");
    }
    // set properties
    $this->session = $session;
    $this->query = Message::find()->where(['SessionId' => $session->id] );
  }

  /**
   * Returns true if new data is available
   *
   * @return bool
   */
  public function check()
  {
    $hasUpdates = $this->query->exists();
    return $hasUpdates;
  }

  /**
   * Returns the new data to be sent to the client
   *
   * @return string
   */
  public function update()
  {
    $idsToDelete = [];
    foreach( $this->query->asArray()->all() as $record ) {
      $d = json_decode($record['data']);
      $data[] = [ 
        'event' => $record['name'],
        'data'  => is_object($d) ? (array) $d : $d
      ];
      $idsToDelete[] = $record['id']; 
    }
    // delete retrieved messages
    Message::deleteAll(['in', 'id', $idsToDelete ]);
    return json_encode($data);
  }

  /**
   * Getter for channel name
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
}