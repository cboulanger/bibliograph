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
 * @todo Rework "noAuthActions" property
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

  //-------------------------------------------------------------
  // Overridden methods
  //-------------------------------------------------------------


  // public function behaviors()
  // {
  //   return [
  //     'authenticator' => [
  //       'class' => \yii\filters\auth\CompositeAuth::className(),
  //       'authMethods' => [
  //         [
  //           'class' => \yii\filters\auth\HttpBearerAuth::className(),
  //           'except' => ['authenticate']
  //         ]
  //       ],
  //     ]
  //   ];
  // }


  /**
   * Filter method to protect action methods from unauthorized access.
   * Uses the JSONRPC 2.0 auth extension or the 'auth' query string parameter
   * as fallback.
   *
   * @param \yii\base\Action $action
   * @return bool True if action can proceed, false if not
   * @throws \yii\web\BadRequestHttpException
   * @throws \yii\db\Exception
   */
  public function beforeAction($action)
  {
    if (!parent::beforeAction($action)) {
      return false;
    }

    // actions without authentication
    if (in_array($action->id, $this->noAuthActions)) {
      return true;
    }

    // on-the-fly authentication with access token
    // first try GET Parameter, then headers
    $token = Yii::$app->request->get('auth_token')
          ?? Yii::$app->request->post('auth_token');
    if ( ! $token ){
      $headers = Yii::$app->request->headers;
      $tryHeaders = ["Authorization","X-Authorization"];
      foreach ($tryHeaders as $header) {
        if ($headers->has($header)) {
          $token = trim( str_replace("Bearer", "", $headers->get($header) ) );
        }
      }
    }
    $user = User::findIdentityByAccessToken($token);
    if (!$token or ! $user or ! $user->active ) {
      Yii::info("No or invalid authorization token '$token'. Access denied.");
      return false;
      // @todo this doesn't work:
      // throw new Exception('Missing authentication', AuthException::INVALID_REQUEST);
    }

    // log in user
    $user->online = 1;
    // FIXME!
    $user->anonymous = (int)$user->anonymous;
    $user->active = (int) $user->active;
    $user->save();
    Yii::$app->user->setIdentity($user);
    /** @var Session $session */
    $session = $this->continueUserSession( $user );
    if ($session) {
      $session->touch();
    }
    $sessionId = $this->getSessionId();
    Yii::info("Authorized user '{$user->namedId}' via auth auth token (Session {$sessionId}).","auth");
    return true;
  }

  /**
   * Shorthand getter for active user object
   * @return \app\models\User
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
    $user->anonymous = $user->active = true;
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
   * Tries to continue an existing session
   *
   * @param User $user
   * @return Session|null
   *    The session object to be reused, or null if none exists.
   */
  protected function continueUserSession($user)
  {
    $session = Session::findOne(['UserId' => $user->id]);
    if ($session) {
      // manually set session id to recover the session data
      // TODO this doesn't work if old stale sessions are still in the database, revisit this
      //session_id( $session->namedId );
    }
    Yii::$app->session->open();
    return $session;
  }

  /**
   * Shorthand getter for  the current session id.
   * @return string
   */
  public function getSessionId()
  {
    return Yii::$app->session->getId();
  }
}