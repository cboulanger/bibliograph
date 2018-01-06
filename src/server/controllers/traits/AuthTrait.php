<?php

namespace app\controllers\traits;
use Yii;

/**
 * This trait contains methods that are useful when authenticating users
 */
trait AuthTrait
{
  /**
   * Checks if the given username has to be authenticated from an LDAP server#
   * @param string $username
   * @throws \InvalidArgumentException if user does not exist
   * @return bool
   */
  public function isLdapUser($username)
  {
    return $this->user($username)->ldap;
  }

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
   * @return string The nounce
   * @throws access_AuthenticationException
   * @todo replace by a (potentially safer) yii equivalent
   */
  public function createNounce($username)
  {
    try {
      $user = $this->user($username);
    } catch (\InvalidArgumentException $e) {
      throw new Exception( $this->tr("Invalid user name or password.") );
    }
  
    $randSalt   = md5(uniqid(rand(), true) );
    $storedSalt = substr( $user->password, 0, ACCESS_SALT_LENGTH );
    $nounce = $randSalt . "|" . $storedSalt;
  
    // store random salt  and return nounce
    $this->setLoginSalt( $randSalt );
    return $nounce;
  }

  /**
   * Stores a login salt in the session
   *
   * @param string $salt
   * @return void
   */
  private function setLoginSalt($salt)
  {
    Yii::$app->session->set('LOGIN_SALT', $salt);
  }
  
  /**
   * Retrieves the login salt from the session
   *
   * @return string
   */
  private function getLoginSalt()
  {
    Yii::$app->session->get('LOGIN_SALT');
  }

}