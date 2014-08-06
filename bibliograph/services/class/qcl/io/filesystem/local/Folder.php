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

qcl_import( "qcl_io_filesystem_local_Resource");
qcl_import( "qcl_io_filesystem_IFolder");

/**
 * Folder-like ressources
 */
class qcl_io_filesystem_local_Folder
  extends qcl_io_filesystem_local_Resource
{

  /**
   * PHP directory object
   * @var Directory
   * @access private
   */
  private $_dir;

  /**
   * Constructor
   * @param string $resourcePath
   */
  public function __construct ( $resourcePath )
  {
    /*
     * parent constructor takes care of controller and resource path
     */
    parent::__construct( $resourcePath );
  }


  /**
   * Creates the folder
   * @param int $mode File permissions, defaults to 0777
   * @return bool if file could be created
   * @throws qcl_io_filesystem_Exception
   */
  public function create($mode=0777)
  {
    /*
     * create folder if it doesn't exist
     */
    $filePath = $this->filePath();

    if ( ! file_exists( $filePath ) )
    {
      if ( ! mkdir( $filePath, $mode ) )
      {
        throw new qcl_io_filesystem_Exception("Problems creating folder '$filePath' with permissions $mode." );
      }
    }
    elseif ( ! is_dir($filePath) )
    {
      throw new qcl_io_filesystem_FileExistsException("File '$filePath' exists but is not a folder.");
    }
    return true;
  }

  /**
   * Creates a file resource if it doesn't exist. Return resource.
   * @param string $name
   * @return qcl_io_filesystem_local_File | false
   * @throws qcl_io_filesystem_Exception
   */
  public function createOrGetFile( $name )
  {
    $resourcePath = $this->resourcePath() . "/" . $name;
    $fileObj = new qcl_io_filesystem_local_File( $resourcePath );
    if ( ! $fileObj->exists() )
    {
      $fileObj->create();
    }
    return $fileObj;
  }

  /**
   * Creates a folder resource if it doesn't exist. Return resource
   * @param string $name
   * @return qcl_io_filesystem_local_Folder | false
   * @throws qcl_io_filesystem_Exception
   */
  public function createOrGetFolder( $name )
  {
    $resourcePath = $this->resourcePath() . "/" . $name;
    $folderObj = new qcl_io_filesystem_local_Folder( $resourcePath );
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
    $filePath = $this->filePath() . "/" . $name;
    return file_exists( $filePath );
  }


  /**
   * Returns the file or folder with the name if it exists
   * @param $name
   * @throws qcl_io_filesystem_FileNotFoundException
   * @return qcl_io_filesystem_local_File | qcl_io_filesystem_local_Folder
   */
  public function get( $name )
  {
    $filePath     = $this->filePath() . "/" . $name;
    $resourcePath = $this->resourcePath() . "/" . $name;

    if ( is_file( $filePath ) )
    {
      return new qcl_io_filesystem_local_File( $resourcePath );
    }
    elseif ( is_dir( $filePath ) )
    {
      return new qcl_io_filesystem_local_Folder( $resourcePath );
    }
    else
    {
      throw new qcl_io_filesystem_FileNotFoundException("File '$filePath' does not exist." ) ;
    }
  }

  /**
   * Opens the folder to iterate through its contents
   * @return void
   */
  public function open()
  {
    $this->_dir = dir( $this->filePath() );
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
      $name = $this->_dir->read();
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
    $this->_dir->close();
  }
}
