<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 15.06.18
 * Time: 08:29
 */

namespace app\controllers\traits;

use app\models\Datasource;
use app\models\Group;
use app\models\Permission;
use app\models\User;
use lib\exceptions\AccessDeniedException;
use lib\exceptions\UserErrorException;
use Sse\Data;
use Yii;

trait AccessControlTrait
{
  /**
   * Convenience method to create a permission with the given named id if it doesn't
   * already exist.
   * @param array|string $namedId The named id(s) of the permission(s)
   * @param string $description Optional description of the permission.
   *    Only used when first argument is a string.
   * @return boolean Whether any of the given permissions were created
   * @throws \yii\db\Exception
   */
  protected function addPermission($namedId, $description = null)
  {
    $created=false;
    if (is_array($namedId)) {
      foreach ($namedId as $id) {
        if ($this->addPermission($id)) $created = true;
      }
      return $created;
    }
    if(! Permission::findByNamedId($namedId) ){
      $permission = new Permission([ 'namedId' => $namedId, 'description' => $description ]);
      $permission->save();
      return true;
    }
    return false;
  }

  /**
   * Removes a permission with the given named id. Silently fails if the
   * permission doesn't exist.
   * @param array|string $namedId The named id(s) of the permission(s)
   * @return void
   */
  protected function removePermission($namedId)
  {
    if (is_array($namedId)) {
      foreach ($namedId as $id) {
        $this->removePermission( $id );
      }
      return;
    }
    Permission::deleteAll(['namedId' => $namedId]);
  }

  /**
   * Returns true if a permission with the given named id exists and false if
   * not.
   * @param string $namedId The named id of the permission
   * @return bool
   */
  protected function permissionExists($namedId)
  {
    return (bool) Permission::findOne(['namedId' => $namedId]);
  }


  /**
   * Given a user and a datasource, return all groups to which both the user and the datasource are linked
   * @param User $user
   * @param Datasource $datasource
   * @return Group[]
   */
  protected function getDatasourceUserGroups( User $user, Datasource $datasource){
    $groups = [];
    $userGroups = $user->getGroupNames();
    $datasourceGroups = $datasource->groups;
    /** @var Group $group */
    foreach( $datasourceGroups as $group){
      if (in_array($group->namedId,$userGroups)){
        $groups[]= $group;
      }
    }
    return $groups;
  }

  /**
   * Checks if active user has the given permission. If a datasource has been specified, check
   * if user has the given permission in this database. If permission  has
   * not been granted, throw.
   *
   * @param string $permission
   * @param Datasource|string|null $datasource
   * @throws \lib\exceptions\Exception
   */
  protected function requirePermission($permission, $datasource = null)
  {
    /** @var User $user */
    $user = $this->getActiveUser();

    if ($user->isAdmin()) return;

    if ($datasource ) {
      if (is_string($datasource)) {
        /** @var Datasource $datasource */
        $datasource = $this->datasource($datasource); // In DatasourceTrait
      } elseif (!($datasource instanceof Datasource)) {
        throw new \InvalidArgumentException("Second argument must be null, string or instanceof Datasource");
      }

      // permissions via group roles
      $groups = $this->getDatasourceUserGroups($user, $datasource);
      /** @var Group $group */
      foreach ($groups as $group) {
        // grant if the user has the permission in that group (or globally)
        if ($user->hasPermission($permission, $group)) {
          return;
        }
      }
      // granted if the user's global roles are linked to the datasource and the permission
      /** Role $role **/
      foreach ($user->getGlobalRoles()->all() as $role) {
        if (in_array($datasource->namedId, $role->datasourceNames) and $role->hasPermission($permission)){
          return;
        }
      }
      // if the user has access to the given database, grant if the user has a role in this database
      // which contains this permission
      if ( $user->hasPermission($permission,null,$datasource)) return;

      // permission not found
      Yii::warning( sprintf(
        "User '%s' does not have required permission '%s' in datasource '%s'",
        $this->getActiveUser()->namedId, $permission, $datasource->namedId
      ));
    } else {
      // global permissions
      if ($user->hasPermission($permission) ) return;
      Yii::warning( sprintf(
        "User '%s' does not have required permission '%s'.",
        $this->getActiveUser()->namedId, $permission
      ));
    }
    throw new \lib\exceptions\AccessDeniedException();
  }

  /**
   * Shorthand method to enforce if active user has a role. If a datasource has been
   * specified, check if user has the given role in this database. If not, throw.
   * @param string $role
   * @param Datasource|string|null $datasource
   * @throws \lib\exceptions\Exception
   */
  protected function requireRole($role, $datasource = null)
  {
    /** @var User $user */
    $user =  $this->getActiveUser();
    if( $datasource ) {
      if (is_string($datasource)) {
        /** @var Datasource $datasource */
        $datasource = $this->datasource($datasource);
      } elseif (!($datasource instanceof Datasource)) {
        throw new \InvalidArgumentException("Second argument must be null, string or instanceof Datasource");
      }
      // group roles
      $groups = $this->getDatasourceUserGroups($user, $datasource);
      /** @var Group $group */
      foreach ($groups as $group) {
        if( $user->hasRole($role, $group)) return;
      }
      Yii::warning( sprintf(
        "User %s does not have required role %s in datasource %s",
        $this->getActiveUser()->namedId, $role, $datasource->namedId
      ));
    } else {
      // global roles
      if( $user->hasRole($role) ) return;
      Yii::warning( sprintf(
        "Active user %s does not have required role %s",
        $this->getActiveUser()->namedId, $role
      ));
    }
    throw new \lib\exceptions\AccessDeniedException();
  }
}
