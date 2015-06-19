<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

/**
 * Interface for Methods common to all filesystem resources
 */
interface qcl_io_filesystem_IResource
{

  /**
   * Checks wether resource path is valid
   * @param string $resourcePath
   * @retrun boolean
   */
  public function checkResourcePath( $resourcePath );

  /**
   * Gets the file's resource path
   * @return string
   */
  public function resourcePath();

  /**
   * Checks if file exists
   * @return bool
   */
  public function exists();

  /**
   * Creates the file
   * @return bool if file could be created
   */
  public function create();

  /**
   * Deletes the file/folder
   * @return booelean Result
   */
  public function delete();

  /**
   * Renames the file/folder Fails if new name exists.
   * @param string $name New name
   * @return boolean Result
   */
  public function rename($name);

  /**
   * Returns the directory in which the resource is located.
   * @param string[optional] $resourcePath
   * @return string
   */
  public function dirname($resourcePath=null);

  /**
   * Returns the name of the (given) resource without the containing directory
   * @param string[optional] $resourcePath
   * @return string
   */
  public function basename($resourcePath=null);

  /**
   * Returns the extension of the (given) resource path, if any.
   * @param string[optional] $resourcePath
   * @return string
   */
  public function extension($resourcePath=null);

  /**
   * Last modification date
   * @return string
   */
  public function lastModified();

  /**
   * Casting as string, returns the resource path
   * @return string
   */
  public function __toString();
}

