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
qcl_import( "qcl_io_filesystem_local_Resource" );
qcl_import( "qcl_io_filesystem_IFile" );

/**
 * Interface for file-like resources
 */
class qcl_io_filesystem_local_File
  extends qcl_io_filesystem_local_Resource
  implements qcl_io_filesystem_IFile
{


  /**
   * A php file pointer
   * @var resource
   * @access private
   */
  var $_fp;

  /**
   * Constructor
   * @param string $resourcePath
   */
  public function __construct ( $resourcePath )
  {
    /*
     * parent constructor takes care of  resource path
     */
    parent::__construct( $resourcePath );

  }

  /**
   * Create a file
   * @param int $mode File permissions, defaults to 0666
   * @throws qcl_io_filesystem_FileExistsException
   * @throws qcl_io_filesystem_Exception
   * @return bool if file could be created
   */
  public function create( $mode=0666 )
  {
    /*
     * create file if it doesn't exist
     */
    $filePath = $this->filePath();
    if ( ! file_exists( $filePath ) )
    {
      if ( touch( $filePath ) )
      {
        return true;
      }
      else
      {
        throw new qcl_io_filesystem_Exception("File '$filePath' could not be created." );
      }
    }
    else
    {
      throw new qcl_io_filesystem_FileExistsException("File '$filePath' already exist." );
    }
  }

  /**
   * Load the whole file resource into memory
   * @return mixed string content or false if file could not be loaded
   */
  public function load()
  {
    return file_get_contents($this->filePath());
  }

  /**
   * save a string of data back into the file resource
   * @param string $data
   * @throws qcl_io_filesystem_Exception
   * @return bool Success
   */
  public function save( $data )
  {
    if ( file_put_contents( $this->filePath(), $data ) )
    {
      return true;
    }
    else
    {
      throw new qcl_io_filesystem_Exception("Problems saving to " . $this->filePath() );
    }
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
    $fp = fopen( $this->filePath(), $mode );
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
    if ( ! fputs( $this->_fp, $data ) )
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
    if ( ! fclose( $this->_fp ) )
    {
      throw new qcl_io_filesystem_Exception("Problem closing " . $this->resourcePath() );
    }
    return true;
  }

  /**
   * Stores data in the file. Shortcut for open("w"), write, close.
   * @param string $data
   */
  public function store( $data )
  {
    $this->open("w");
    $this->write($data);
    $this->close();
  }

  /**
   * Apends data to file. Shortcut for open("a"), write, close
   * @param string $data
   */
  public function append( $data )
  {
    $this->open("a");
    $this->write($data);
    $this->close();
  }

  /**
   * Returns the size of the file
   * @return int
   */
  public function size()
  {
    return filesize( $this->filePath() );
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

}
