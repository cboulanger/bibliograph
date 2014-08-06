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


/**
 * Methods common to all filesystem resources
 */
class qcl_io_filesystem_Resource
  extends qcl_core_Object
{
  /**
   * The file resource path
   *
   * @var string
   * @var access private
   */
  protected $resourcePath;

  /**
   * The supported / allowed protocols
   */
  protected $resourceTypes = array();

  /**
   * The currently used protocol
   */
  protected $resourceType;

  /**
   * Constructor
   * @param string $resourcePath
   * @throws qcl_io_filesystem_Exception
   */
  public function __construct ( $resourcePath )
  {
    parent::__construct();

    /*
     * check resource path
     */
    if ( ! $this->checkResourcePath( $resourcePath ) )
    {
      throw new qcl_io_filesystem_Exception("'$resourcePath' is not a valid resource for " . $this->className() );
    }

    /*
     * protocol
     */
    $this->resourceType = $this->getResourceType( $resourcePath );

    /*
     * save resource path
     */
    $this->setResourcePath( $resourcePath );
  }

  /**
   * Default setter for resource path
   * @param string $resourcePath
   * @return void
   */
  public function setResourcePath( $resourcePath )
  {
    $this->resourcePath = $resourcePath;
  }

  /**
   * Factory method which returns the correct class type according to
   * the protocol. Folder paths MUST end with a slash.
   * @static
   * @param string $resourcePath
   * @throws qcl_core_NotImplementedException
   * @return qcl_io_filesystem_IResource
   */
  static function createInstance( $resourcePath )
  {
    /*
     * check resource path.
     */
    $resourceType = qcl_io_filesystem_Resource::getResourceType( $resourcePath );
    switch ( $resourceType )
    {
      case "file":
        if ( substr($resourcePath,-1) == "/" )
        {
          require_once "qcl/io/filesystem/local/Folder.php";
          return new qcl_io_filesystem_local_Folder( $resourcePath );
        }
        else
        {
          require_once "qcl/io/filesystem/local/File.php";
          return new qcl_io_filesystem_local_File( $resourcePath );
        }
        break;

      default:
        throw new qcl_core_NotImplementedException("Cannot create resource instance for '$resourcePath'. Remote folders not implemented yet.");
        /*
        if ( substr($resourcePath,-1) == "/" )
        {
          require_once "qcl/io/filesystem/remote/Folder.php";
          return new qcl_io_filesystem_remote_Folder( $resourcePath );
        }
        else
        {
          require_once "qcl/io/filesystem/remote/File.php";
          return new qcl_io_filesystem_remote_File( $resourcePath );
        }
        break;
        */
    }

  }

  /**
   * Returns the prefix of the resource path as the protocol/ resource
   * type
   */
  static function getResourceType( $resourcePath )
  {
    return substr( $resourcePath, 0, strpos($resourcePath,":") );
  }

  /**
   * Checks wether resource path is valid. Local files have to start
   * with "file://", remote files with a valid protocol such as "ftp://"
   * @param string $resourcePath
   * @return bool
   * @retrun boolean
   */
  public function checkResourcePath( $resourcePath )
  {
    $pos = strpos($resourcePath,":");
    return  in_array( substr($resourcePath, 0, $pos), $this->resourceTypes )
            && substr($resourcePath,$pos,3) == "://";
  }

  /**
   * Gets the file's resource path
   * @alias getResourcePath
   * @return string
   */
  public function resourcePath()
  {
    return $this->resourcePath;
  }


  /**
   * Gets the file's resource path
   * @return string
   */
  public function getResourcePath()
  {
    return $this->resourcePath;
  }


  /**
   * Returns the file path withoug leading protocol "foo://"
   * @param string[optional] $resourcePath The path to the resource, otherwise the one
   * of the current resource object
   * @return string
   */
  public function filePath( $resourcePath=null )
  {
    $rp = either( $resourcePath, $this->resourcePath() );
    return substr( $rp, strlen( $this->resourceType ) +3 );
  }

  /**
   * Returns the directory in which the (given) resource is located.
   * @param string[optional] $resourcePath
   * @return string
   */
  public function dirname( $resourcePath = null)
  {
    $rp  = either ( $resourcePath, $this->resourcePath() );
    return substr( $rp, 0, strrpos($rp, "/" ) );
  }

  /**
   * Returns the name of the (given) resource path without the containing directory
   * @param string[optional] $resourcePath
   * @return string
   */
  public function basename( $resourcePath=null )
  {
    $rp  = either ( $resourcePath, $this->resourcePath() );
    $pos = strrpos( $rp, "/" );
    if ( $pos == strlen( $rp )-1 )
    {
      $pos = strrpos(substr( $rp, 0, -1), "/");
    }
    if ( $pos !== false )
    {
      return substr( $this->resourcePath(), $pos+1 );
    }
    return $rp;
  }

  /**
   * Returns the extension of the (given) resource path, if any.
   * @param string[optional] $resourcePath
   * @return string
   */
  public function extension( $resourcePath=null )
  {
    $rp  = either ( $resourcePath, $this->resourcePath() );
    $bn  = $this->basename( $rp );
    $pos = strrpos( $bn, "." );
    if ( $pos !== false )
    {
      return substr($bn,$pos+1);
    }
    return "";
  }

  /**
   * Casting as string, returns the resource path
   * @return string
   */
  public function __toString()
  {
    return $this->resourcePath();
  }
}
