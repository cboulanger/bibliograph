<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 01.06.18
 * Time: 21:44
 */

namespace app\controllers;
use lib\exceptions\UserErrorException;
use lib\models\ClipboardContent;
use yii\db\Exception;

/**
 * Class ClipboardController
 * Implements a user-level clipboard
 */
class ClipboardController extends AppController
{

  /**
   * Adds a clipboard entry for a given mime type and the current user
   * @param string $mime_type
   * @param string $data
   * @return string Diagnostic message
   */
  public function actionAdd( string $mime_type, string $data)
  {
    $userId = $this->getActiveUser()->id;
    $entry = ClipboardContent::find()
      ->where(['UserId' => $userId])
      ->andWhere(['mime_type' => $mime_type])
      ->one();
    if( ! $entry ){
      $entry = new ClipboardContent([
        'mime_type' => $mime_type,
        'data'      => $data,
        'UserId'    => $userId
      ]);
      $message = "Clipboard entry created.";
    } else {
      $entry->data = $data;
      $message = "Clipboard entry updated.";
    }
    try {
      $entry->save();
    } catch (Exception $e) {
      throw new UserErrorException($e->getMessage());
    }
    $this->dispatchClientMessage("clipboard.add", (object)[
      'mime_type' => $mime_type,
      'data'      => $data
    ]);
    return $message;
  }

  /**
   * Returns the current user's clipboard data for a given mime type
   * @param string $mime_type
   * @return string|null The entry data, if it exists, or null if not
   */
  public function actionGet( string $mime_type )
  {
    $userId = $this->getActiveUser()->id;
    return ClipboardContent::find()
      ->where(['UserId' => $userId])
      ->andWhere(['mime_type' => $mime_type])
      ->one();
  }
}