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

qcl_import("qcl_io_filesystem_local_Resource");

/**
 * Base class for remote filesystem resource classes
 */
class qcl_io_filesystem_remote_Resource
  extends qcl_io_filesystem_local_Resource
{
  /**
   * The supported / allowed protocols
   */
  var $resourceTypes = array("ftp","http","s3");

  /**
   * Constructor. Checks whether allow_url_fopen us enabled (necessary for
   * the class to work) and registers s3 streamwrapper if necessary
   * @param string $resourcePath
   * @throws qcl_io_filesystem_Exception
   */
  public function __construct ( $resourcePath )
  {
    /*
     * parent constructor takes care of resource path
     */
    parent::__construct( $resourcePath );

    /*
     * s3 wrapper
     */
    if ( $this->resourceType == "s3" )
    {
      if ( ! defined('S3_KEY') )
      {
        throw new qcl_io_filesystem_Exception("You need to define the S3_KEY constant");
      }
      if ( ! defined('S3_PRIVATE') )
      {
        throw new qcl_io_filesystem_Exception("You need to define the S3_PRIVATE constant");
      }
      require_once "qcl/lib/gs3/gs3.php";
    }

    /*
     * check for allow_url_fopen
     */
    if ( ! ini_get("allow_url_fopen") )
    {
      throw new qcl_io_filesystem_Exception("You need to enable 'allow_url_fopen' in the php.ini file for this to work.");
    }
  }

  /**
   * Checks whether (given) resource path is a file. This is not a real check,
   * the path is analyzed whether there is a file extension.
   * @param string[optional] $resourcePath
   * @return bool
   */
  public function isFile( $resourcePath = null )
  {
    $rp  = either ( $resourcePath, $this->resourcePath() );
    if ( $rp[strlen($rp)-1] == "/" ) return false;
    $ext = $this->extension( $this->basename($rp ) );
    return $ext != "";
  }

  /**
   * Checks whether (given) resource path is a directory.This is not a real check,
   * the path is analyzed whether there is a file extension.
   * @param string[optional] $resourcePath
   * @return bool
   * @todo this should be clear from the file name!
   */
  public function isDir( $resourcePath=null )
  {
    $rp  = either ( $resourcePath, $this->resourcePath() );
    if ( $rp[strlen($rp)-1] == "/" ) return true;
    $ext = $this->extension( $this->basename($rp ) );
    return $ext == "";
  }

  /**
   * Checks if file or folder exists
   */
  public function exists()
  {
    if ( $this->open() )
    {
      $this->close();
      return true;
    }
    return false;
  }

  /**
   * Deletes the file/folder. Not implemented
   * @todo implement, if possible at all
   * @throws qcl_io_filesystem_Exception
   * @return booelean Result
   */
  public function delete()
  {
    if ( ! unlink( $this->resourcePath() ) )
    {
      throw new qcl_io_filesystem_Exception("Problem deleting " . $this->resourcePath() );
    }
    return true;
  }

  /**
   * Renames the file/folder Fails if new name exists. Not implemented
   * @todo Implement where possible
   * @param string $name New name
   * @throws qcl_core_NotImplementedException
   * @return boolean Result
   */
  public function rename($name)
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  /**
   * Returns the last modification date
   */
  public function lastModified()
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }


}
