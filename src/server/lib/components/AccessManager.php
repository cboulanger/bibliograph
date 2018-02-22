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

namespace lib\components;

use app\models\User;
use Yii;

class AccessManager
{

  /**
   * Calling this method with a single argument (the plain text password)
   * will cause a random string to be generated and used for the salt.
   * The resulting string consists of the salt followed by the SHA-1 hash
   * - this is to be stored away in your database. When you're checking a
   * user's login, the situation is slightly different in that you already
   * know the salt you'd like to use. The string stored in your database
   * can be passed to generateHash() as the second argument when generating
   * the hash of a user-supplied password for comparison.
   *
   * See http://phpsec.org/articles/2005/password-hashing.html
   * @todo use YII method instead
   * @param $plainText
   * @param $salt
   * @return string
   */
  public function generateHash($plainText, $salt = null)
  {
    if ($salt === null) {
      $salt = substr( md5(uniqid(rand(), true) ), 0, ACCESS_SALT_LENGTH);
    } else {
      $salt = substr($salt, 0, ACCESS_SALT_LENGTH );
    }
    return $salt . sha1( $salt . $plainText);
  }

  /**
   * Create a one-time token for authentication. It consists of a random part and the
   * salt stored with the password hashed with this salt, concatenated by "|".
   * @param string $username
   * @return string The nonce
   * @throws \InvalidArgumentException
   * @todo replace by a (potentially safer) yii equivalent
   */
  public function createNonce($username)
  {
    $user = User::findByNamedId($username);
    if( ! $user ){
      throw new \InvalidArgumentException("User '$username' does not exist");
    }
    $randSalt = md5(uniqid(rand(), true) );
    $storedSalt = substr( $user->password, 0, ACCESS_SALT_LENGTH );
    $nonce = $randSalt . "|" . $storedSalt;
    // store random salt  and return nonce
    $this->setLoginSalt( $randSalt );
    return $nonce;
  }

  /**
   * Stores a login salt in the session
   *
   * @param string $salt
   * @return void
   */
  public function setLoginSalt($salt)
  {
    Yii::$app->session->set('ACCESS_LOGIN_SALT', $salt);
  }

  /**
   * Retrieves the login salt from the session
   *
   * @return string
   */
  public function getLoginSalt()
  {
    return Yii::$app->session->get('ACCESS_LOGIN_SALT');
  }


//  /**
//   * Returns number of seconds since resetLastAction() has been called
//   * for the current user
//   * @return int seconds
//   */
//  public function getSecondsSinceLastAction()
//  {
//    $now = new data_db_Timestamp();
//    $lastAction = $this->get("lastAction");
//    if ($lastAction) {
//      $d = $now->diff($lastAction);
//      return (int) ($d->s + (60 * $d->i) + (3600 * $d->h) + 3600 * 24 * $d->d);
//    }
//    return 0;
//  }
}