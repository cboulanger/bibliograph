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
qcl_import( "qcl_io_filesystem_remote_File");
qcl_import( "qcl_io_filesystem_remote_Folder");

/**
 * Class modeling a datasource containing files stored on a
 * remote computer. Currently does not support subfolders.
 * Supports all protocols supported by php plus amazon s3.
 * Must be sublassed in order to be used.
 */
abstract class qcl_io_filesystem_remote_Datasource
  extends qcl_data_datasource_DbModel
{

  /**
   * The name of the schema
   * @var string
   */
  protected $schemaName = "qcl.schema.filesystem.remote";

  /**
   * The description of the schema
   * @var string
   */
  protected $description =
    "A datasource providing access to remote files ...";

  /**
   * The type of the datasource. Needs to be set by the
   * subclass
   *
   * @var string
   */
  protected $type = null;

  /**
   * The folder containing the files in this datasource
   * @var qcl_io_filesystem_remote_Folder
   */
  protected $folderObj = null;

 /**
   * The model properties
   */
  private $properties = array(
    'schema' => array(
      'nullable'  => false,
      'init'      => "qcl.schema.filesystem.remote"
    )
  );

  /**
   * Constructor, overrides some properties
   * @return \qcl_io_filesystem_remote_Datasource
   */
  public function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
  }

  /**
   * If the datasource is a file storage. True for this datasource
   * @return bool
   */
  public function isFileStorage()
  {
    return true;
  }

  /**
   * Returns the file object to do read and write operations with.
   * @param string $filename
   * @var qcl_io_filesystem_remote_File
   * @throws LogicException
   */
  public function getFile($filename)
  {
    return $this->getFolderObj()->get($filename);
  }

  /**
   * Returns the folder object of the datasource
   * @throws LogicException
   */
  public function getFolderObject()
  {
    if ( ! $this->folderObj )
    {
      /*
       * initialize s3 file storages
       * FIXME must go into specialized s3 sublcass
       */
      if ( $this->getType() == "s3" )
      {
        qcl_assert_array_keys( $this->data(), array("resourcePath","username","password") );
        define("S3_KEY",     $this->getUsername() );
        define('S3_PRIVATE', $this->getPassword() );
      }
      $resourcePath = $this->getType() . "://" . $this->getResourcePath();
      $this->folderObj = new qcl_io_filesystem_remote_Folder( $resourcePath);
    }
    return $this->folderObj;
  }
}
