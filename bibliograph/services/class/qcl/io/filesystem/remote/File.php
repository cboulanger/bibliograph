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

qcl_import( "qcl_io_filesystem_remote_Resource" );
qcl_import( "qcl_io_filesystem_IFile" );

/**
 * Interface for file-like resources stored on a remote computer
 * Supports all protocols/wrappers supported by PHP
 */
class qcl_io_filesystem_remote_File
  extends qcl_io_filesystem_remote_Resource
  implements qcl_io_filesystem_IFile
{


  /**
   * A php file pointer
   * @var resource
   * @access private
   */
  var $_fp;

  /**
   * Constructor. Will create the file if it doesn't exist and will
   * throw an error if that is not possible.
   * @param string $resourcePath
   * @internal param \qcl_data_controller_Controller $controller
   */
  public function __construct ( $resourcePath )
  {
    /*
     * parent constructor takes care of controller and resource path
     */
    parent::__construct($resourcePath );

  }

  /**
   * Checks if file exists
   * @return bool
   */
  public function exists()
  {
    if ( $this->open("r") )
    {
      $this->close();
      return true;
    }
    return false;
  }

  /**
   * Creates the file
   * @param string $data optional data to write to the file
   * @return bool success
   * @throws qcl_io_filesystem_Exception
   */
  public function create($data="")
  {
    if ( $this->open("w") )
    {
      $result = $this->write($data);
      $this->close();
      return $result;
    }
    throw new qcl_io_filesystem_Exception("Problem creating file " . $this->resourcePath() );
  }

  /**
   * Load the whole file resource into memory
   * @return mixed string content or false if file could not be loaded
   */
  public function load()
  {
    if ( $this->open("r") )
    {
      $data = "";
      while ( $b = $this->read(4096) )
      {
        $data .= $b;
      }
      $this->close();
      return $data;
    }
    return false;
  }

  /**
   * save a string of data back into the file resource
   * @param string $data
   * @return bool Success
   */
  public function save($data)
  {
    if ( $this->open("w") )
    {
      $result = $this->write($data);
      $this->close();
      return $result;
    }
    return false;
  }

  /**
   * Opens the file resource for reading or writing
   * @param string $mode r(ead)|w(rite)|a(append)
   * @return bool
   * @throws qcl_io_filesystem_Exception
   * @return bool
   * @internal param \Result $boolean
   */
  public function open($mode="r")
  {
    $fp = fopen( $this->resourcePath(), $mode );
    if ( ! $fp )
    {
      throw new qcl_io_filesystem_Exception("Problem opening " . $this->resourcePath() );
    }
    $this->_fp = $fp;
    return true;
  }

  /**
   * Reads a variable number of bytes from the resource
   * @param int $bytes
   * @throws qcl_io_filesystem_Exception
   * @return string|false|null Tthe string read, false if there was an error and null if end of file was reached
   */
  public function read( $bytes )
  {
    if ( ! $this->_fp )
    {
      throw new qcl_io_filesystem_Exception("You have to ::open() the file first.");
    }
    if ( feof( $this->_fp) )
    {
      return null;
    }
    $result = fread($this->_fp,$bytes);
    if ( ! $result )
    {
      throw new qcl_io_filesystem_Exception("Problem reading $bytes from " . $this->resourcePath() );
    }
    return $result;
  }

  /**
   * Reads one line from the resource
   * @throws qcl_io_filesystem_Exception
   * @internal param int $bytes
   * @return string|false|null Tthe string read, false if there was an error and null if end of file was reached
   */
  public function readLine()
  {
    if ( feof( $this->_fp) )
    {
      return null;
    }
    $result = fgets($this->_fp);
    if ( ! $result )
    {
      throw new qcl_io_filesystem_Exception("Problem reading line from " . $this->resourcePath() );
    }
    return $result;
  }

  /**
   * Writes to the file resource a variable number of bytes
   * @param string $data
   * @return bool
   * @throws qcl_io_filesystem_Exception
   * @return bool
   */
  public function write( $data )
  {
    if ( ! fputs($this->_fp,$data ) )
    {
      throw new qcl_io_filesystem_Exception("Problem writing to " . $this->resourcePath() );
    }
    return true;
  }

  /**
   * Closes the file resource
   * @throws qcl_io_filesystem_Exception
   * @return booelean Result
   */
  public function close()
  {
    if ( ! fclose($this->_fp) )
    {
      throw new qcl_io_filesystem_Exception("Problem closing " . $this->resourcePath() );
    }
    return true;
  }


  /**
   * Returns an associative array containing information about path.
   * The following associative array elements are returned:
   * dirname, basename extension (if any), and filename.
   * @return array
   **/
  public function pathinfo()
  {
    return pathinfo($this->resourcePath());
  }

  function size()
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }
}
