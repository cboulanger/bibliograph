<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 15.06.18
 * Time: 08:26
 */

namespace app\controllers\traits;
use Yii;
use yii\base\Exception;

trait ShelfTrait
{
  /**
   * Temporarily stores the supplied arguments on the server for retrieval
   * by another service method. This storage is only guaranteed to last during
   * the current session and is then discarded. The method can take a variable
   * number of arguments
   * @return string
   *    The shelf id needed to retrieve the data later
   */
  public function shelve()
  {
    try {
      $shelfId = Yii::$app->security->generateRandomString();
    } catch (Exception $e) {
      $shelfId = str_random();
    }
    Yii::$app->session->set($shelfId,func_get_args());
    //$_SESSION[$shelfId] = func_get_args();
    return $shelfId;
  }

  /**
   * Retrieve the data stored by the shelve() method.
   * @param $shelfId
   *    The id of the shelved data
   * @param bool $keepCopy
   *    If true, the data will be preserved and can be retrieved again.
   *    If false or omitted, the data will be deleted.
   * @return array
   *    Returns an array of the elements passed to the shelve() method, which can be
   *    extracted with the list() method.
   */
  public function unshelve($shelfId, $keepCopy=false )
  {
    $args =  Yii::$app->session->get($shelfId);
    //$args = $_SESSION[$shelfId];
    if ( !$keepCopy ) {
      //unset( $_SESSION[$shelfId] );
      Yii::$app->session->remove( $shelfId );
    }
    return $args;
  }

  /**
   * Returns true if something is stored und the shelf id
   * @param string $shelfId
   * @return bool
   */
  public function hasInShelf( $shelfId ){
    if( empty($shelfId) ) return false;
    return Yii::$app->session->has( $shelfId );
  }
}