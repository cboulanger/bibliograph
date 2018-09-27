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

use Adldap\Auth\BindException;
use Yii;
use app\models\User;
use app\models\Group;
use app\models\Role;

/**
 * Component class providing methods to get or set configuration
 * values
 */
class LdapAuth extends \yii\base\Component
{
  const CATEGORY = "ldap";

  /**
   * Checks if LDAP is enabled and that a connection can be established
   *
   * @return array 
   *    An associated array with the keys 'enabled' (bool), 'connection' (bool) and
   *    'error' (string).
   */
  public function checkConnection()
  {
    $connection = false;
    $error = null;
    try {
      $ldapEnabled    = Yii::$app->config->getIniValue("ldap.enabled");
      $bind_dn        = Yii::$app->config->getIniValue("ldap.bind_dn");
      $bind_password  = Yii::$app->config->getIniValue("ldap.bind_password");
    } catch (\InvalidArgumentException $e) {
      $ldapEnabled = false;
      $error = "Invalid ldap configuration in ini file:" . $e->getMessage();
    }
    if( $ldapEnabled ){
      try {
        Yii::$app->ldap->connect("default");
        $connection = true;
      } catch ( BindException $e) {
        $error = "Can't connect / bind to the LDAP server:" . $e->getMessage();
      }
    }
    return [
      'enabled'     => $ldapEnabled,
      'connection'  => $connection,
      'error'       => $error,
    ];    
  }

  /**
   * Authenticate using a remote LDAP server.
   * @param $username
   * @param $password
   * @return \app\models\User|null User or null if authentication failed
   * @throws \RuntimeException
   * @throws \Adldap\Models\ModelNotFoundException
   * @throws \yii\db\Exception
   */
  public function authenticate( $username, $password )
  {
    $user_base_dn = Yii::$app->config->getIniValue( "ldap.user_base_dn" );
    $user_id_attr = Yii::$app->config->getIniValue("ldap.user_id_attr");
    $alternative_auth_attrs = Yii::$app->config->getIniValue("ldap.alternative_auth_attrs");
    $bind_attrs = [$user_id_attr];
    if (is_array($alternative_auth_attrs)){
      $bind_attrs = array_merge( $bind_attrs, $alternative_auth_attrs);
    }
    $ldap = Yii::$app->ldap;
    $attr = array_shift($bind_attrs);
    do {
      $bind_dn = "$attr=$username";
      Yii::debug("Trying to bind $bind_dn with LDAP Server...", self::CATEGORY);
      try {
        if ($ldap->auth()->attempt($bind_dn, $password, true)) {
          Yii::debug("Success!", self::CATEGORY);
          break;
        }
      } catch (BindException $e) {
        throw new \RuntimeException(
          Yii::t('app', "Cannot connect to the LDAP server: {reason}", [
            'reason' => $e->getMessage()
          ])
        );
      }
      // auth failed, but maybe we can get the id from this attribute and try again
      if ($attr !== $user_id_attr){
        $records = $ldap->search()
          ->in( $user_base_dn )
          ->select([ $user_id_attr ])
          ->where( $attr, "=", $username )
          ->recursive()
          ->get();
        if (count($records)){
          $data = $records[0]->jsonSerialize();
          if (isset($data[$user_id_attr][0] )){
            $username = $data[$user_id_attr][0];
            Yii::debug("Found entry for $attr=$username in $user_base_dn", self::CATEGORY);
            array_unshift($bind_attrs, $user_id_attr);
          }
        }
      }
    } while ($attr = array_shift($bind_attrs));

    // we've run out of attributes to match, authentication failed
    if (! $attr ) {
      Yii::debug("User/Password combination is wrong.", self::CATEGORY);
      return null;
    }

    // if LDAP authentication succeeds, assume we have a valid
    // user. if this user does not exist, create it with "user" role
    // and assign it to the groups specified by the ldap source
    $user = User::findOne(['namedId'=>$username]);
    if( ! $user) {
      $user = $this->createUser( $username );
    }

    // update group membership
    $this->updateGroupMembership( $username );
    return $user;
  }

  /**
   * Identifies a user at a remote LDAP server and creates local user if they exist.
   * Returns the user model instance
   * @param $username
   * @return \app\models\User|null User or null if user does not exist
   * @throws \RuntimeException
   * @throws \Adldap\Models\ModelNotFoundException
   * @todo code duplication with authenticate() method
   * @throws \yii\db\Exception
   */
  public function identify($username)
  {
    $user_base_dn = Yii::$app->config->getIniValue( "ldap.user_base_dn" );
    $user_id_attr = Yii::$app->config->getIniValue("ldap.user_id_attr");
    $alternative_auth_attrs = Yii::$app->config->getIniValue("ldap.alternative_auth_attrs");
    $bind_attrs = [$user_id_attr];
    if (is_array($alternative_auth_attrs)){
      $bind_attrs = array_merge( $bind_attrs, $alternative_auth_attrs);
    }
    $ldap = Yii::$app->ldap;
    $attr = array_shift($bind_attrs);
    do {
      Yii::debug("Searching for $attr=$username in $user_base_dn ...", self::CATEGORY);
      $records = $ldap->search()
        ->in( $user_base_dn )
        ->select([ $user_id_attr ])
        ->where( $attr, "=", $username )
        ->recursive()
        ->get();
      if (count($records)){
        $data = $records[0]->jsonSerialize();
        if (isset($data[$user_id_attr][0] )){
          $username = $data[$user_id_attr][0];
          Yii::debug("Found user '$username'", self::CATEGORY);
          break;
        }
      }
    } while ($attr = array_shift($bind_attrs));

    // we've run out of attributes to match, search failed
    if (! $attr ) {
      Yii::debug("No user '$username' can be found", self::CATEGORY);
      return null;
    }

    // if user does not exist locally, create it with "user" role
    // and assign it to the groups specified by the ldap source
    $user = User::findOne(['namedId' => $username]);
    if( ! $user) {
      $user = $this->createUser( $username );
    }

    // update group membership
    $this->updateGroupMembership( $username );
    return $user;
  }


  /**
   * Creates a new user from a LDAP database. The
   * default behavior is to use the attributes "cn", "sn","givenName"
   * to determine the user's full name and the "mail" attribute to
   * determine the user's email address. Returns the newly created local user.
   *
   * @param string $username
   * @return \app\models\User
   * @throws \Adldap\Models\ModelNotFoundException
   * @throws \yii\db\Exception
   */
  protected function createUser( $username )
  {
    $app = Yii::$app;
    $config = $app->config;
    $ldap = $app->ldap; 
    $user_base_dn = $config->getIniValue( "ldap.user_base_dn" );
    $user_id_attr = $config->getIniValue( "ldap.user_id_attr" );
    $mail_domain  = $config->getIniValue( "ldap.mail_domain" );
    
    $dn = "$user_id_attr=$username,$user_base_dn";
    Yii::debug("Retrieving user data from LDAP by distinguished name '$dn'",self::CATEGORY);
  
    $record = $ldap->search()
      ->select(["cn", "displayName", "sn", "givenName","mail" ])
      ->findByDnOrFail($dn);

    // this can probably be written more efficiently
    list( $cn, $displayName, $sn, $givenName, $email ) = [
      $record->getCommonName(),
      $record->getDisplayName(),
      $record->getFirstAttribute("sn"),
      $record->getFirstAttribute("givenName"),
      $record->getEmail()
    ];
    
    // Full name
    ($name = $cn ) ?: 
    ($name = $displayName ) ?:
    ($name = "$givenName $sn") ?: 
    ($name = $username);

    // Email address
    if ( $email and $mail_domain ) {
      $email .= "@" . $mail_domain;
    }

    // create new user without any role
    // @todo import first and last name
    $user = new User([
      'namedId'   => $username,
      'name'      => $name,
      'email'     => $email,
      'ldap'      => 1,
      'online'    => 1,
      'active'    => 1,
      'anonymous' => 0,
      'confirmed' => 1 // an LDAP user needs no confirmation
    ]);
    $user->save();
    $user->link('roles', Role::findByNamedId("user") );
    Yii::info("Created local user '$name' from LDAP data and assigned 'user' role ...", self::CATEGORY );
    //Yii::debug( $user->getAttributes(null, ['token']) );
    return $user;
  }

  /**
   * Updates the group memberships of the user from the ldap database
   * @param $ldap
   * @param $username
   * @return void
   * @throws \yii\db\Exception
   */
  protected function updateGroupMembership( $username )
  {
    $app = Yii::$app;
    $config = $app->config;
    $ldap = $app->ldap; 
    if ( ! $config->getIniValue("ldap.use_groups") ){
      // don't use groups
      return;
    }

    $group_base_dn      = $config->getIniValue( "ldap.group_base_dn" );
    $group_name_attr    = $config->getIniValue( "ldap.group_name_attr" );
    $group_member_attr  = $config->getIniValue( "ldap.group_member_attr" );

    Yii::debug("Retrieving group data from LDAP...", self::CATEGORY);

    $ldapGroups = [];
    if( $group_member_attr and $group_base_dn ){
      $ldapGroups = $ldap->search()
        ->in( $group_base_dn )
        ->select([ "cn", $group_name_attr ])
        ->where( $group_member_attr, "=", $username )
        ->recursive()
        ->get();
    }

    if ( count($ldapGroups) == 0 ) {
      Yii::debug("User '$username' belongs to no LDAP groups", self::CATEGORY);
    }    
    
    $user = User::findOne(['namedId'=>$username]);
    assert(is_object($user),"User record must exist at this point");

    $groupNames = $user->getGroupNames();

    if( count($groupNames) == 0 and count($ldapGroups) == 0 ){
      Yii::debug("User '$username' belongs to no local groups. Nothing to do.", self::CATEGORY );
      return;
    }
    Yii::debug("User '$username' is member of the groups " . implode(", ", $groupNames), self::CATEGORY );

    // parse entries and update groups if neccessary
    foreach( $ldapGroups as $ldapGroup ) {
      $namedId = $ldapGroup->getCommonName();
      $group = Group::findByNamedId($namedId);
      if( ! $group ){      
        $name  = $ldapGroup->getFirstAttribute($group_name_attr);
        Yii::debug("Creating group '$namedId' ('$name') from LDAP", self::CATEGORY );
        $group = new Group([
          'namedId' => $namedId,
          'name'    => $name,
          self::CATEGORY    => true,
          'active'  => 1,
         ]);
         $group->save();
      }

      // make user a group member
      if ( ! in_array( $namedId, $groupNames ) ){
        Yii::debug("Adding user '$username' to group '$namedId'", self::CATEGORY );
        try {
          $group->link( 'users', $user );
        } catch ( \Exception $e ) {
          Yii::warning($e->getMessage(), __METHOD__ . ":" . __LINE__);
          Yii::error($e);
        }
      } else {
        Yii::debug("User '$username' is already member of group '$namedId'", self::CATEGORY );
      }

      // if group provides a default role
      $defaultRole = $group->defaultRole;
      if ( $defaultRole ) {
        $role = Role::findByNamedId($defaultRole);
        if( ! $role ){
          $error = "Default role '$role' does not exist.";
          // @todo generalize this:
          if ( YII_ENV_DEV ) throw new \InvalidArgumentException($error);
          Yii::error($error);
        }
        $condition = [ 'RoleId' => $role->id, 'GroupId' => $group->id ];
        if( $role and ! $user->getUserRoles()->where($condition)->exists() )
        {
          Yii::debug("Granting user '$username' the default role '$defaultRole' in group '$namedId'", self::CATEGORY);
          try {
            $user->link( 'roles', $role, [ 'GroupId' => $group->id ] );
          } catch ( \Exception $e ) {
            Yii::warning($e->getMessage(), __METHOD__ . ":" . __LINE__);
            Yii::error($e);
          }
        }
      }
      // tick off (remove) group name from the list
      $groupNames = array_diff($groupNames, [$namedId]);
    }

    // remove all remaining user from all groups that are not listed in LDAP
    foreach( $groupNames as $namedId )
    {
      $group = Group::findByNamedId($namedId);
      assert(\is_object($group),"Group must exist."); 
      if ( $group->ldap ) {
        Yii::debug("Removing user '$username' from group '$namedId'", self::CATEGORY);
        $user->unlink( 'groups', $group );
      } else {
        Yii::warning("Not removing user '$username' from group '$namedId': not a LDAP group", self::CATEGORY);
      }
    }
    Yii::debug( "User '$username' is member of the following groups: " . implode(",", $user->getGroupNames() ), self::CATEGORY);
  }

  /**
   * Returns an array LDAP records that match a certain name, or an empty array if none matches
   * @param $identifier
   * @return array Array of associative arrays with the keys "namedId" (the LDAP user id) and "name" (taken from the
   * CN attribute)
   * @throws \RuntimeException
   * @todo Make "name" attribute configurable in ini file
   */
  public function find($identifier )
  {
    $user_base_dn = Yii::$app->config->getIniValue( "ldap.user_base_dn" );
    $user_id_attr = Yii::$app->config->getIniValue("ldap.user_id_attr");
    $alternative_auth_attrs = Yii::$app->config->getIniValue("ldap.alternative_auth_attrs");
    $auth_attrs = [$user_id_attr];
    if (is_array($alternative_auth_attrs) ) {
      $auth_attrs = array_merge($auth_attrs, $alternative_auth_attrs);
    }
    $ldap = Yii::$app->ldap;
    $search = $ldap->search()->in( $user_base_dn )->select($auth_attrs);
    foreach ($auth_attrs as $attr){
      $search = $search->orWhere( $attr, "contains", $identifier);
    }
    $records = $search->recursive()->get();
    $data =[];
    if (count($records)){
      foreach ($records as $record){
        $d = $record->jsonSerialize();
        $data[] = [
          'namedId' => $d[$user_id_attr][0],
          'name'    => isset($d['cn'][0]) ?  $d['cn'][0] : ""
        ];
      }
    }
    return $data;
  }
}