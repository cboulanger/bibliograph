<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_data_model_db_NamedActiveRecord" );

/**
 *
 */
class bibliograph_model_export_RegistryModel
  extends qcl_data_model_db_NamedActiveRecord
{

  //-------------------------------------------------------------
  // Model properties
  //-------------------------------------------------------------

  protected $tableName = "data_ExportFormat";

  /**
   * The model properties
   */
  private $properties = array(
    'class' => array(
      'check'     => "string",
      'sqltype'   => "varchar(100)",
      'nullable'  => false,
      'init'      => "invalid"
    ),
    'name' => array(
      'check'     => "string",
      'sqltype'   => "varchar(100)",
      'nullable'  => false,
      'init'      => "invalid"
    ),
    'description' => array(
      'check'     => "string",
      'sqltype'   => "varchar(255)",
      'nullable'  => true
    ),
    'active' => array(
      'check'     => "boolean",
      'sqltype'   => "tinyint(1)",
      'nullable'  => false,
      'init'      => true
    ),
    'type'   => array(
      'check'     => "string",
      'sqltype'   => "varchar(20)"
    ),
    'extension' => array(
      'check'     => "string",
      'sqltype'   => "varchar(20)",
      'nullable'  => false,
      'init'      => "txt"
    )
  );

  //-------------------------------------------------------------
  // Initialization
  //-------------------------------------------------------------

  /**
   * Constructor
   */
  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
  }

  /**
   * Returns singleton instance of this class.
   * @return bibliograph_model_export_RegistryModel
   */
  public static function getInstance()
  {
    return qcl_getInstance(__CLASS__);
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Adds an exporter by the given class
   * @param $class
   * @return void
   */
  public function addFromClass( $class )
  {
    qcl_import( $class );
    $exporter = qcl_getInstance( $class );
    qcl_assert_instanceof( $exporter, "bibliograph_model_export_AbstractExporter" );

    $this->createIfNotExists( $exporter->getId(), array(
      'class'       => $class,
      'name'        => $exporter->getName(),
      'description' => $exporter->getDescription(),
      'type'        => $exporter->getType(),
      'extension'   => $exporter->getExtension()
    ) );
  }

  /**
   * Returns the export engine that provides the given export format
   * @param $format
   * @throws JsonRpcException
   * @return bibliograph_model_export_AbstractExporter
   */
  public function getExporter( $format )
  {
    try
    {
      $this->load( $format );
      $class = $this->getClass();
      qcl_import( $class );
      return qcl_getInstance( $class );
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      throw new JsonRpcException("Export format '$format' does not exist.");
    }
  }
}
?>