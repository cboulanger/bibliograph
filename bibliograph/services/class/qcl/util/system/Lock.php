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

qcl_import("qcl_core_Object");

/**
 * A simple locking system to make sure two users aren't accessing
 * the same resource at the same time.
 *
 */
class qcl_util_system_Lock
  extends qcl_core_Object
{

  /**
   * The name of the lock
   * @var string
   */
  private $name;

  /**
   * A file pointer for this lock
   * @var resource
   */
  private $fp;

  /**
   * The path to the temporary file
   * @var string
   */
  private $file;

  /**
   * Constructor
   * @param $name
   * @return \qcl_util_system_Lock
   */
  public function __construct( $name )
  {
    $this->name = $name;
    $this->file = sys_get_temp_dir() . "/$name";
  }

  /**
   * Checks if lock is already taken
   * @return boolean
   */
  public function isLocked()
  {
    return file_exists( $this->file );
  }

  /**
   * Tries to get the lock
   * @return bool
   *    True if the lock for this file was available,
   *    false if it was already locked.
   */
  public function getExclusive()
  {
    if( ! file_exists( $this->file ) )
    {
      touch( $this->file );
      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * Releases the lock
   * @return void
   */
  public function release()
  {
    if ( file_exists( $this->file ) )
    {
      unlink( $this->file );
    }
    else
    {
      $this->warn("Lock '{$this->name}' does not exist");
    }
  }
}
?>