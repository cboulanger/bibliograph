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

qcl_import( "qcl_data_datasource_DbModel");
qcl_import( "qcl_io_filesystem_local_File");
qcl_import( "qcl_io_filesystem_local_Folder");

/**
 * Class modeling a datasource containing files stored on the local computer.
 * Currently does not support subfolders
 */
class qcl_io_filesystem_local_Datasource
  extends qcl_data_datasource_DbModel
{

  /**
   * The name of the schema
   * @var string
   */
  protected $schemaName = "qcl.schema.filesystem.local";

  /**
   * The description of the schema
   * @var string
   */
  protected $description =
    "A datasource providing access to local files ...";

  /**
   * The type of the datasource.
   * @var string
   */
  protected $type = "file";

  /**
   * The folder containing the files in this datasource
   * @var qcl_io_filesystem_local_Folder
   */
  protected $folderObj = null;

 /**
   * The model properties
   */
  private $properties = array(
    'schema' => array(
      'nullable'  => false,
      'init'      => "qcl.schema.filesystem.local"
    ),
    'type' => array(
      'nullable'  => false,
      'init'      => "file"
    )
  );

  /**
   * Constructor, overrides some properties
   * @return \qcl_io_filesystem_local_Datasource
   */
  public function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
  }

  /**
   * Returns singleton instance of this class.
   * @return qcl_io_filesystem_local_Datasource
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  public function isFileStorage()
  {
    return true;
  }

  /**
   * Returns the file object to do read and write operations with.
   * @param string $filename
   * @var qcl_io_filesystem_local_File
   * @return \qcl_io_filesystem_local_File|\qcl_io_filesystem_local_Folder
   * @throws LogicException
   */
  public function getFile($filename)
  {
    return $this->getFolderObject()->get($filename);
  }

  /**
   * Returns the folder object of the datasource
   * @throws LogicException
   */
  public function getFolderObject()
  {
    if ( ! $this->folderObj )
    {
      $resourcePath = $this->getType() . "://" . $this->getResourcepath();
      $this->folderObj = new qcl_io_filesystem_local_Folder( $resourcePath );
      if( ! $this->folderObj->exists() )
      {
        throw new JsonRpcException( sprintf(
          "Resource path '%s' of datasource '%s' points to a non-existing or non-accessible resource.",
           $resourcePath, $this->namedId()
        ) );
      }
    }
    return $this->folderObj;
  }

  /**
   * Returns a list of fields that should be disabled in a form
   * @override
   * @return array
   */
  public function unusedFields()
  {
    return array( "host", "port", "username", "password", "database", "prefix");
  }
}
