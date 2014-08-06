<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import("qcl_data_Result");

class qcl_access_AuthenticationResult
  extends qcl_data_Result
{
   /**
    * Authentication error, if any
    * @var false|string
    */
   public $error;

   /**
    * An array of permissions
    * @var array
    */
   public $permissions;

   /**
    * The session id
    * @var string
    */
   public $sessionId;

   /**
    * The login name of the user
    * @var string
    */
   public $username;

   /**
    * The full name of the user
    * @var string
    */
   public $fullname;

   /**
    * The user id
    * @var int
    */
   public $userId;

   /**
    * Whether the user is an unauthenticated guest
    * @var boolean
    */
   public $anonymous;

   /**
    * Whether the user data is editable
    * @var boolean
    */
   public $editable = false;
}
