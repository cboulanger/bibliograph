<?php

namespace app\controllers\traits;

use Yii;

use app\models\User;
use app\models\Role;
use app\models\Permission;
use app\models\Group;
use app\models\Session;

/**
 * This trait contains methods that are usefule when working with
 * Role-based Access Control (RBAC)
 */
trait RbacTrait
{

  /**
   * Shorthand getter for active user object
   * @return \app\models\User
   */
  public function getActiveUser()
  {
    return Yii::$app->user->identity;
  }

  /**
   * Creates a new anonymous guest user
   * @throws LogicException
   * @return int \app\models\User
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
   * Returns true if a permission with the given named id exists and false if
   * not.
   * @param string $namedId The named id of the permission
   * @return bool
   */
  public function permissionExists($namedId)
  {
    return (bool) Permission::findOne(['namedId' => $namedId]);
  }

  /**
   * Creates a permission with the given named id if it doesn't
   * already exist.
   * @param array|string $namedId The named id(s) of the permission(s)
   * @param string $description Optional description of the permission.
   *    Only used when first argument is a string.
   * @return void
   */
  public function addPermission($namedId, $description = null)
  {
    if (is_array($namedId)) {
      foreach ($namedId as $id) {
        $this->addPermission( $id );
      }
      return;
    }
    $permission = new Permission([ 'namedId' => $namedId, 'description' => $description ]);
    $permission->save();
  }

  /**
   * Removes a permission with the given named id. Silently fails if the
   * permission doesn't exist.
   * @param array|string $namedId The named id(s) of the permission(s)
   * @return void
   */
  public function removePermission($namedId)
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
   * Deletes a user
   */
  public function deleteUser($user)
  {
    $this->dispatchMessage("user.deleted", $user->id());
    $user->delete();
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
    if (!  $this->getActiveUser()->hasPermission( $permission )) {
      $this->warn( sprintf(
        "Active user %s does not have required permission %s",
        $this->getActiveUser()->namedId, $permission
      ) );
      throw new \JsonRpc2\Exception("Not allowed.", \JsonRpc2\Exception::INVALID_REQUEST);
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
        $this->getActiveUser()->namedId, $role
      ) );
        throw new Exception("Access denied.");
    }
  }
}