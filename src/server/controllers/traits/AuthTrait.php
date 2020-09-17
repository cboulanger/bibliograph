<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 15.03.18
 * Time: 08:18
 */

namespace app\controllers\traits;

use Yii;
use app\models\{
  User, Role, Session
};


/**
 * Trait AuthTrait
 * @package app\controllers\traits
 */
trait AuthTrait
{

  /**
   * Array of action names that can be accessed without authentication
   * Implement this propery in subclasses or define a getter for dynamic
   * definition
   * @var array
   */
  //protected $noAuthActions = [];

  /**
   * @return array
   */
  protected function getNoAuthActions()
  {
    return $this->noAuthActions ?? [];
  }

  /**
   * Shorthand getter for active user object
   * @return User
   */
  public function getActiveUser()
  {
    /** @var User $activeUser */
    $activeUser = Yii::$app->user->identity;
    return $activeUser;
  }

  /**
   * Creates a new anonymous guest user
   * @throws \LogicException
   * @return \app\models\User
   * @throws \yii\db\Exception
   */
  public function createAnonymous()
  {
    $anonRole = Role::findByNamedId('anonymous');
    if (is_null($anonRole)) {
      throw new \LogicException("No 'anonymous' role defined.");
    }
    $user = new User(['namedId' => \microtime() ]); // random temporary username
    $user->save();
    $user->namedId = "guest" . $user->getPrimaryKey();
    $user->name = "Guest";
    $user->anonymous = $user->active = 1;
    $user->save();
    $user->link("roles", $anonRole);
    return $user;
  }

  /**
   * Returns the [[app\models\User]] instance of the user with the given
   * username.
   *
   * @param string $username
   * @throws \InvalidArgumentException if user does not exist
   * @return \app\models\User
   */
  public function user($username)
  {
    $user = User::findOne(['namedId'=>$username]);
    if (is_null($user)) {
      throw new \InvalidArgumentException( Yii::t('app',"User '$username' does not exist.") );
    }
    return $user;
  }

  /**
   * Shorthand to reset the current session and generate a new session id
   */
  public function resetSession() {
    Yii::$app->session->regenerateID();
  }

  /**
   * Shorthand getter for  the current session id.
   * @return string
   */
  public function getSessionId()
  {
    return Yii::$app->session->getId();
  }

  /**
   * This deletes the given session. Unless the second argument is true, delete
   * the corresponding user if it is an anonymous user and this is the only session.
   * @param string $sessionId
   * @param bool $doNotDeleteAnonymousUser
   * @throws \Throwable
   * @throws \yii\db\StaleObjectException
   */
  public function deleteSessionIfExists($sessionId, $doNotDeleteAnonymousUser=false) {
    $session = Session::findOne($sessionId);
    if ($session) {
      if (!$doNotDeleteAnonymousUser) {
        /** @var User $user */
        $user = $session->getUser()->one();
        if ($user->isAnonymous() and $user->getSessions()->count() === 1) {
          $user->delete();
        }
      }
      $session->delete();
    }
  }
}
