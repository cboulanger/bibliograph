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

qcl_import("qcl_io_filesystem_local_File");

/**
 * Temporary File
 */
class qcl_io_filesystem_local_TempFile
  extends qcl_io_filesystem_local_File
{

  /**
   * Constructor. Will create the file if it doesn't exist and will
   * throw an error if that is not possible.
   */
  public function __construct ( )
  {
    /*
     * resource path is a temporary file
     */
    $resourcePath = "file://" . tempnam(null,"");
    if ( ! $resourcePath )
    {
      throw new qcl_io_filesystem_Exception("Problem creating temporary file.");
    }

    /*
     * parent constructor takes care of controller and resource path
     */
    parent::__construct( $resourcePath );
  }

  /**
   * Destructor. Deletes file at the end of the script.
   */
  public function __destruct()
  {
    $this->delete();
  }
}
?>