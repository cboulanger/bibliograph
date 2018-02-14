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
 /**
   * Authenticate using a remote LDAP server.
   * @param $username
   * @param $password
   * @return \app\models\User|null User or null if authentication failed
   * @throws 
   */
  public function authenticate( $username, $password )
  {
    $app = Yii::$app;
    $user_id_attr = Yii::$app->config->getIniValue("ldap.user_id_attr");
    $bind_dn = "$user_id_attr=$username";
    Yii::trace("Trying to bind $bind_dn with LDAP Server.", 'ldap');
    try {
      if ( ! $app->ldap->auth()->attempt( $bind_dn, $password, true )) {
        Yii::trace("User/Password combination is wrong.", 'ldap');
        return null; 
      }  
    } catch (\Adldap\Auth\BindException $e) {
      $error = "Can't connect to the LDAP server: " . $e->getMessage();
      // @todo generatlize this:
      if ( YII_ENV_DEV ) throw new \Exception($error);
      Yii::error($error);
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
   * Creates a new user from an authenticated LDAP connection. The
   * default behavior is to use the attributes "cn", "sn","givenName"
   * to determine the user's full name and the "mail" attribute to
   * determine the user's email address. Returns the newly created local user.
   *
   * @param string $username
   * @return \app\models\User
   * @throws \Adldap\Models\ModelNotFoundException 
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
    Yii::trace("Retrieving user data from LDAP by distinguished name '$dn'",'ldap');
  
    $record = $ldap->search()
      ->select(["cn", "sn","givenName","mail" ])
      ->findByDnOrFail($dn);
    
    // Full name
    if( isset( $record['cn'][0] ) ) {
      $name = $record['cn'][0];
    } elseif ( isset( $record['sn'][0] ) and isset( $record['givenName'][0] ) ) {
      $name = $record['givenName'][0] . " " . $record['sn'][0];
    } elseif ( isset( $record['sn'][0] ) ) {
      $name = $record['sn'][0];
    } else {
      $name = $username;
    }

    // Email address
    if ( isset( $record['mail'][0] ) ) {
      $email = $record['mail'][0];
      if ( $mail_domain ) {
        $email .= "@" . $mail_domain;
      }
    } else {
      $email = "";
    }

    // create new user without any role
    // @todo import first and last name
    $user = new User([
      'namedId'   => $username,
      'name'      => $name,
      'email'     => $email,
      'ldap'      => 1,
      'confirmed' => 1 // an LDAP user needs no confirmation
    ]);
    $user->save();
    $user->link('roles', Role::findByNamedId("user") );
    Yii::trace("Created local user '$name' from LDAP data and assigned 'user' role ...", 'ldap' );
    return $user;
  }

  /**
   * Updates the group memberships of the user from the ldap database
   * @param $ldap
   * @param $username
   * @return void
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

    $group_base_dn   = $config->getIniValue( "ldap.group_base_dn" );
    $member_id_attr  = $config->getIniValue( "ldap.member_id_attr" );
    $group_name_attr = $config->getIniValue( "ldap.group_name_attr" );

    Yii::trace("Retrieving group data from LDAP...", 'ldap' );
    $records = $ldap->search()
      ->select([ "cn", $group_name_attr ])
      ->where( $member_id_attr, "=", $username )
      ->get();
    
    if ( count($records) == 0 ) {
      Yii::trace("User '$username' belongs to no LDAP groups", 'ldap' );
    }

    $user = User::findOne(['namedId'=>$username]);
    assert(is_object($user),"User record must exist at this point");
    $groupNames = $user->getGroupNames();

    if( count($groupNames) == 0 and count($records) == 0 ){
      Yii::trace("User '$username' belongs to no local groups. Nothing to do.", 'ldap' );
      return;
    }

    // parse entries and update groups if neccessary
    foreach( $records as $record ) {
      Yii::trace($record);
      continue;
      $namedId = $entries[$i]['cn'][0];
      $group = Group::findByNamedId($namedId);
      if( ! $group ){      
        $name  = $entries[$i][$group_name_attr][0];
        Yii::trace("Creating group '$namedId' ('$name') from LDAP", 'ldap' );
        $group = new Group([
          'namedId' => $namedId,
          'name'    => $name,
          'ldap'    => true
         ]);
         $group->save();
      }

      // make user a group member
      if ( ! $user->getGroups()->where(['namedId'=>'$namedId'])->exists() ){
        Yii::trace("Adding user '$username' to group '$namedId'", 'ldap' );
        $group->link( 'groups', $user );
      }

      // if group provides a default role
      $defaultRole = $group->defaultRole;
      if ( $defaultRole )
      {
        $role = Role::findByNamedId($defaultRole);
        if( ! $role ){
          $error = "Default role '$role' does not exist.";
          // @todo generatlize this:
          if ( YII_ENV_DEV ) throw new \InvalidArgumentException($eror);
          Yii::error($error);
        }
        $condition = [ 'RoleId' => $role->id, 'GroupId' => $group->id ];
        if( $role and ! $user->getUserRoles()->where($condition)->exists() )
        {
          Yii::trace("Granting user '$username' the default role '$defaultRole' in group '$namedId'", 'ldap' );
          $user->link( 'roles', $role, [ 'GroupId' => $group->id ] );
        }
      }
      // tick off (remove) group name from the list
      $groupNames = array_diff($groupNames, [$namedId]);
    }

    // remove all remaining user from all groups that are not listed in LDAP
    foreach( $groupNames as $namedId )
    {
      $group = Group::findByNamedId($namedId);
      assert(\is_object($group)); // group must exist
      if ( $group->ldap ) {
        Yii::trace("Removing user '$username' from group '$namedId'", 'ldap' );
        $user->unlink( 'groups', $group );
      } else {
        Yii::warn("Not removing user '$username' from group '$namedId': not a LDAP group", 'ldap' );
      }
    }
    Yii::trace( "User '$username' is member of the following groups: " . implode(",", $user->getGroupNames() ), 'ldap' );
  }
}