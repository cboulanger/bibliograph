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

namespace app\controllers;

use Yii;

use app\models\User;
use app\models\Session;

/**
 * Service class providing methods to get or set configuration
 * values
 */
class AppController extends \JsonRpc2\Controller
{
  use traits\ShimTrait;

  public function xxxbehaviors()
  {
    return [
      'authenticator' => [
        'class' => \yii\filters\auth\CompositeAuth::className(),
        'authMethods' => [
          [
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
            'except' => ['authenticate']
          ]
        ],
      ]
    ];
  }

  /**
   * Returns the [[app\models\User]] instance of the user with the given
   * username.
   *
   * @param string $username
   * @throws InvalidArgumentException if user does not exist
   * @return \app\models\User
   */
  public function user($username)
  {
    $user = User::findOne(['namedId'=>$username]);
    if (is_null($user)) {
      throw new \InvalidArgumentException( $this->tr("User '$username' does not exist.") );
    }
    return $user;
  }

  /**
   * Shorthand getter for active user object
   * @return \app\models\User
   */
  public function getActiveUser()
  {
    return Yii::$app->user->identity;
  }

  /**
   * Tries to continue an existing session
   *
   * @param \app\models\User $user
   * @return \app\model\Session|null The session object to be reused, or null
   * if none exists.
   */
  protected function continueUserSession($user)
  {
    $session = Session::findOne(['UserId' => $user->id]);
    if ($session) {
      // manually set session id to recover the session data
      session_id( $session->namedId );
    }
    Yii::$app->session->open();
    return $session;
  }

  /**
   * Filter method to protect action methods from unauthorized access.
   * Uses the JSONRPC 2.0 auth extension or the 'auth' query string parameter
   * as fallback.
   *
   * @param \yii\base\Action $action
   * @return boolan True if action can proceed, false if not
   */
  public function beforeAction($action)
  {
    if (!parent::beforeAction($action)) {
      return false;
    }

    // authenticate action is always allowed
    if (in_array($action->id, ["authenticate"])) {
      return true;
    }

    // on-the-fly authentication with access token
    $token = null;
    $headers = Yii::$app->request->headers;
    $tryHeaders = ["Authorization","X-Authorization"];
    foreach ($tryHeaders as $header) {
      if ($headers->has($header)) {
        $token = trim( str_replace("Bearer", "", $headers->get($header) ) );
      }
    }
    if (!$token or ! $user = User::findIdentityByAccessToken($token)) {
      Yii::info("No or invalid authorization token '$token'. Access denied.");
      return false;
      // @todo this doesn't work:
      // throw new AuthException('Missing authentication', AuthException::MISSING_AUTH);
    }

    // log in user
    $user->online = true;
    $user->save();
    Yii::$app->user->setIdentity($user);
    $session = $this->continueUserSession( $user );
    if ($session) {
      $session->touch();
    }
    $sessionId = $this->getSessionId();
    Yii::info("Authorized user '{$user->namedId}' via auth auth token (Session {$sessionId}.");
    return true;
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
   * Checks if active user has the given permission.
   * @param $permission
   * @return bool
   */
  public function activeUserhasPermission($permission)
  {
    return $this->getActiveUser()->hasPermission( $permission );
  }

  /**
   * Checks if active user has the given permission and aborts if
   * permission is not granted.
   *
   * @param string $permission
   * @return bool
   * @throws Exception if access is denied
   */
  public function requirePermission($permission)
  {
    if (!  $this->activeUserhasPermission( $permission )) {
      $this->warn( sprintf(
      "Active user %s does not have required permission %s",
      $this->getActiveUser(), $permission
      ) );
        throw new AuthException('Missing authentication', AuthException::MISSING_AUTH);
    }
  }

  /**
   * Shorthand method to enforce if active user has a role
   * @param string $role
   * @throws qcl_access_AccessDeniedException
   * @return bool
   */
  public function requireRole($role)
  {
    if (! $this->getActiveUser()->hasRole( $role )) {
      $this->warn( sprintf(
      "Active user %s does hat required role %s",
      $this->getActiveUser(), $role
      ) );
        throw new Exception("Access denied.");
    }
  }

  //-------------------------------------------------------------
  // Datasources
  //-------------------------------------------------------------

  /**
   * Returns a list of datasources that is accessible to the current user.
   * Accessibility is restricted by the group-datasource, the role-datasource
   * relation and the user-datasource relation.
   *
   * @return array
   */
  public function getAccessibleDatasources()
  {
    not_implemented();
    static $datasources = null;

    if ($datasources === null) {
    }
    sort( $datasources );
    return array_unique( $datasources );
  }

  /**
   * Checks if user has access to the given datasource. If not,
   * throws JsonRpcException.
   * @param string $datasource
   * @return void
   * @throws JsonRpcException
   */
  public function checkDatasourceAccess($datasource)
  {
    if ($this->controlDatasourceAccess === true and
    ! in_array( $datasource, $this->getAccessibleDatasources() ) ) {
      $dsModel = $this->getDatasourceModel( $datasource );
      throw new JsonRpcException( $this->tr("You don't have access to '%s'", $dsModel->getName() ) );
    }
  }
}
