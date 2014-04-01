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
 *
 * NOT FUNCTIONAL YET
 */

qcl_import("qcl_io_filesystem_remote_Resource");
qcl_import("qcl_io_filesystem_remote_File");


/**
 * Folder-like ressources
 */
class qcl_io_filesystem_remote_Folder
  extends qcl_io_filesystem_remote_Resource
//  implements qcl_io_filesystem_IFolder
{

  /**
   * Constructor. Will create the folder if it doesn't exist and will
   * throw an error if that is not possible.
   * @param string $resourcePath
   * @param int $mode File permissions, defaults to 0777
   * @throws qcl_io_filesystem_Exception
   */
  public function __construct ( $resourcePath, $mode=0777 )
  {
    /*
     * parent constructor takes care of controller and resource path
     */
    parent::__construct( $resourcePath );

    /*
     * check for trailing slash
     */
    if ( substr($resourcePath,-1) != "/" )
    {
      throw new qcl_io_filesystem_Exception("Invalid resource path '$resourcePath': must end with a slash for folders!");
    }
  }

  /**
   * Creates a file resource if it doesn't exist. Return resource.
   * @param string $name
   * @return qcl_io_filesystem_remote_File|false
   */
  public function createOrGetFile( $name )
  {
    /*
     * create file if it doesn't exist
     */
    $resourcePath = $this->resourcePath() . $name;
    $fileObj = new qcl_io_filesystem_remote_File( $resourcePath );
    if ( ! $fileObj->exists() )
    {
      $fileObj->create();
    }
    return $fileObj;

  }

  /**
   * Creates a folder resource if it doesn't exist. Return resource
   * @param string $name
   * @return qcl_io_filesystem_remote_Folder|false
   */
  public function createOrGetFolder( $name )
  {
    /*
     * create directory if it doesn't exist
     */
    $resourcePath = $this->resourcePath() . $name ."/";
    $folderObj = new qcl_io_filesystem_remote_Folder( $resourcePath );
    if ( ! $folderObj->exists() )
    {
      $folderObj->create();
    }
    return $folderObj;
  }

  /**
   * Checks if resource of the given name exists in this folder
   * @param string $name
   * @return boolean
   */
  public function has( $name )
  {
    $file = $this->get($name);
    if ( $file->open() )
    {
      $file->close();
      return true;
    }
    return false;
  }

  /**
   * Returns the file or folder with the name if it exists
   * @param $name
   * @throws qcl_io_filesystem_Exception
   * @return qcl_io_filesystem_local_File | qcl_io_filesystem_local_Folder
   */
  public function get( $name )
  {
    $resourcePath = $this->resourcePath() . $name;

    if ( $this->isFile( $resourcePath ) )
    {
      return new qcl_io_filesystem_remote_File( $resourcePath );
    }
    elseif ( $this->isDir( $resourcePath ) )
    {
      return new qcl_io_filesystem_remote_Folder( $resourcePath );
    }
    else
    {
      throw new qcl_io_filesystem_Exception("Invalid file type '$resourcePath'." ) ;
    }
  }

  /**
   * Opens the folder to iterate through its contents
   * @return void
   */
  public function open()
  {
    $this->_dir = opendir( $this->resourcePath() );
  }

  /**
   * Gets the next entry in the folder
   * @throws qcl_io_filesystem_Exception
   * @return qcl_io_filesystem_local_File | qcl_io_filesystem_local_Folder
   */
  public function next()
  {
    /*
     * check if dir has been opened
     */
    if ( ! $this->_dir )
    {
      throw new qcl_io_filesystem_Exception("You have to open() the directory first.");
    }

    /*
     * get next element, skipping "." and ".."
     */
    do
    {
      $name = readdir($this->_dir);
    }
    while ( $name =="." or $name == ".." );

    /*
     * valid file or folder
     */
    if ( $name )
    {
      return $this->get($name);
    }

    /*
     * no further content
     */
    return false;
  }

  /**
   * Closes the folder resource
   */
  public function close()
  {
    closedir($this->_dir);
  }

}
?>