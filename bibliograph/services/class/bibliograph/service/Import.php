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

qcl_import( "qcl_data_controller_TableController" );
qcl_import( "bibliograph_model_import_RegistryModel" );

/**
 *
 */
class bibliograph_service_Import
  extends qcl_data_controller_TableController
{

  /**
   * Access control list. Determines what role has access to what kind
   * of information.
   * @var array
   */
  private $modelAcl = array(

    /*
     * The reference model of the given datasource
     */
    array(
      'datasource'  => "bibliograph_import",
      'modelType'   => "reference",

      'rules'         => array(
        array(
          'roles'       => array( BIBLIOGRAPH_ROLE_USER ),
          'access'      => "*",
          'properties'  => array( "allow" => "*" )
        )
      )
    )
  );

  /*
  ---------------------------------------------------------------------------
     INITIALIZATION
  ---------------------------------------------------------------------------
  */

  /**
   * Constructor, adds model acl
   */
  function __construct()
  {
    $this->addModelAcl( $this->modelAcl );
  }

  /**
   * Returns singleton instance of this class
   * @return bibliograph_service_Import
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }


  /*
  ---------------------------------------------------------------------------
     TABLE INTERFACE API
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param $datasource
   * @return unknown_type
   */
  public function method_getTableLayout( $datasource )
  {
    return array(
      'columnLayout' => array(
        'id' => array(
          'header'  => "ID",
          'width'   => 50,
          'visible' => false
        ),
        'author' => array(
          'header'  => _("Author"),
          'width'   => "1*"
        ),
        'year' => array(
          'header'  => _("Year"),
          'width'   => 50
        ),
        'title' => array(
          'header'  => _("Title"),
          'width'   => "3*"
        )
      ),
      'queryData' => array(
        'orderBy' => "author,year,title",
        'link'    => array( 'relation' => "Folder_Reference" ),
      ),
      'addItems' => null
    );
  }

  /*
  ---------------------------------------------------------------------------
     OTHER SERVICES
  ---------------------------------------------------------------------------
  */

  public function method_getImportFormatData()
  {
    $registry = bibliograph_model_import_RegistryModel::getInstance();
    $registry->findAllOrderBy("name");
    $listData = array( array(
      'value' => null,
      'label' => _("2. Choose import format" )
    ) );
    while( $registry->loadNext() )
    {
      if ( $registry->getActive() )
      {
        $listData[] = array(
          'value' => $registry->namedId(),
          'label' => $registry->getName()
        );
      }
    }
    return $listData;
  }

  /**
   * Process the uploaded file with the given format.
   *
   * @param string $file
   *    Path to the uploaded file.
   * @param string $format
   *    The name of the import format
   * @throws JsonRpcException
   * @return array
   *    An array containint the key "folderId" with the integer
   *    value of the folder containing the processed references.
   */
  public function method_processUpload( $file, $format )
  {
    $this->requirePermission("reference.import");
    qcl_assert_valid_string( $format, "Invalid format");
    qcl_assert_file_exists( $file );

    /*
     * load importer object according to format
     */
    $importRegistry = bibliograph_model_import_RegistryModel::getInstance();
    $importer = $importRegistry->getImporter( $format );

    $extension = either( get_file_extension( $file ), $format);
    

    if( $extension !== $importer->getExtension() )
    {
      throw new JsonRpcException(sprintf(
        _("Format '%s' expects file extension '%s'. The file you uploaded has extension '%s'"),
        $importer->getName(),
        $importer->getExtension(),
        $extension
      ));
    }

    /*
     * get the folder and reference models
     */
    $dsModel  = $this->getDatasourceModel("bibliograph_import");
    $refModel = $dsModel->getInstanceOfType("reference");
    $fldModel = $dsModel->getInstanceOfType("folder");

    /*
     * delete the folder with the session id as label
     */
    $sessionId = $this->getSessionId();
    try
    {
      $fldModel->findWhere( array('label' => $sessionId ) );
      $fldModel->loadNext();
      $refModel->findLinked( $fldModel );
      while( $refModel->loadNext() ) $refModel->delete();
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      $fldModel->create( array( 'label' => $sessionId ) );
    }

    /*
     * convert and import data
     */
    $data = file_get_contents( $file );
    $records = $importer->import( $data );
    foreach( $records as $record )
    {
      $refModel->create( $record );
      $refModel->linkModel($fldModel);
    }

    /*
     * return information on containing folder
     */
    return array(
      'folderId' => $fldModel->id()
    );
  }

  /**
   * Imports the given references from one datasource into another
   * @param string $sourceDatasource
   * @param array $ids
   * @param string $targetDatasource
   * @param int $targetFolderId
   * @return string "OK"
   */
  public function method_importReferences( $sourceDatasource, $ids, $targetDatasource, $targetFolderId )
  {
    $this->requirePermission("reference.import");

    qcl_assert_valid_string( $sourceDatasource );
    qcl_assert_array( $ids );
    qcl_assert_valid_string( $targetDatasource );
    qcl_assert_integer( $targetFolderId );
    qcl_import("bibliograph_service_Reference");
    qcl_import("bibliograph_service_Folder");

    $sourceModel = $this->getModel( $sourceDatasource, "reference" );

    $targetReferenceModel =
      bibliograph_service_Reference::getInstance()
      ->getReferenceModel($targetDatasource);

    $targetFolderModel =
      bibliograph_service_Folder::getInstance()
      ->getFolderModel( $targetDatasource );

    $targetFolderModel->load( $targetFolderId );

    foreach( $ids as $id )
    {
      $sourceModel->load($id);
      $targetReferenceModel->create();
      $targetReferenceModel->copySharedProperties( $sourceModel );
      $targetReferenceModel->save();
      $targetFolderModel->linkModel( $targetReferenceModel );
    }

    /*
     * update reference count
     */
    $referenceCount = count( $targetReferenceModel->linkedModelIds( $targetFolderModel ) );
    $targetFolderModel->set( "referenceCount", $referenceCount );
    $targetFolderModel->save();

    /*
     * reload references and select the new reference
     */
    $this->dispatchClientMessage("folder.reload", array(
      'datasource'  => $targetDatasource,
      'folderId'    => $targetFolderId
    ) );

    return "OK";
  }


  public function method_test()
  {
    qcl_import("qcl_util_system_Executable");
    $xml2bib = new qcl_util_system_Executable( BIBUTILS_PATH . "xml2bib");
    $xml2bib->call( "-v" );
    $this->info( "stdout: " . $xml2bib->getStdOut() );
    $this->info( "stderr: " . $xml2bib->getStdErr() );
  }
}
?>