<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
   2007-2017 Christian Boulanger

   License:
   LGPL: http://www.gnu.org/licenses/lgpl.html
   EPL: http://www.eclipse.org/org/documents/epl-v10.php
   See the LICENSE file in the project's top-level directory for details.

   Authors:
   * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use Yii;

use \JsonRpc2\Exception;

use app\controllers\AppController;
use app\models\ImportFormat;

/**
 *
 */
class ImportController extends AppController
{
  use traits\ShimTrait;
  use traits\RbacTrait;
  use traits\AuthTrait;


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
  public function actionGetTableLayout( $datasource )
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

  /**
   * Returns the list of import formats for a selectbox
   */
  public function actionImportformats()
  {
    $importFormats = ImportFormat::find()->where(['active' => 1])->orderBy("name")->all();
    $listData = array( array(
      'value' => null,
      'label' => _("2. Choose import format" )
    ) );
    foreach( $importFormats as $format ){
      $listData[] = array(
        'value' => $format->namedId,
        'label' => $format->name
      );
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

//    $extension = either( get_file_extension( $file ), $format);
//
//    if( $extension !== $importer->getExtension() )
//    {
//      throw new JsonRpcException(sprintf(
//        _("Format '%s' expects file extension '%s'. The file you uploaded has extension '%s'"),
//        $importer->getName(),
//        $importer->getExtension(),
//        $extension
//      ));
//    }

    // get the folder and reference models
    $dsModel  = $this->getDatasourceModel("bibliograph_import");
    $refModel = $dsModel->getInstanceOfType("reference");
    $fldModel = $dsModel->getInstanceOfType("folder");
    
    // cleanup unused data: purge all folders with names of sessions that no longer exist
    $sessionModel=$this->getAccessController()->getSessionModel();
    $fldModel->findAll();
    while( $fldModel->loadNext() )
    {
      try
      {
        $sessionModel->load( $fldModel->getLabel() );
      }
      catch( qcl_data_model_RecordNotFoundException $e )
      {
        try
        {
          $refModel->findLinked( $fldModel );
          while( $refModel->loadNext() ) $refModel->delete();
        }
        catch( qcl_data_model_RecordNotFoundException $e ){}
        $fldModel->delete();
      }
    }
    
    // empty an existing folder with the session id as label
    $sessionId = $this->getSessionId();
    $fldModel->findWhere( array('label' => $sessionId ) );
    if($fldModel->foundSomething())
    {
      $fldModel->loadNext();
      try
      {
        $refModel->findLinked( $fldModel );
        //$this->debug("Emptying folder $sessionId");
        while( $refModel->loadNext() ) $refModel->delete();  
      }
      catch( qcl_data_model_RecordNotFoundException $e)
      {
        //$this->debug("No content in folder $sessionId");
      }
    }
    else
    {
      //$this->debug("Creating folder $sessionId");
      $fldModel->create( array( 'label' => $sessionId ) );
      $fldModel->setParentId(0)->save();
    }
    
    /*
     * convert and import data
     */
    $data = file_get_contents( $file );
    
    // convert to utf-8
    if (!preg_match('!!u', $data))
    {
      throw new JsonRpcException($this->tr("You must convert file to UTF-8 before importing."));
    }
    
    
    $records = $importer->import( $data, $refModel );
    foreach( $records as $record )
    {
      $refModel->create( $record );
      if( ! $refModel->getCitekey() )
      {
        $refModel->setCitekey($refModel->computeCitekey())->save();
      }
      
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
  public function method_importReferences( $ids, $targetDatasource, $targetFolderId )
  {
    $this->requirePermission("reference.import");

    qcl_assert_array( $ids );
    qcl_assert_valid_string( $targetDatasource );
    qcl_assert_integer( $targetFolderId );
    
    qcl_import("bibliograph_service_Reference");
    qcl_import("bibliograph_service_Folder");
    
    $dsModel  = $this->getDatasourceModel("bibliograph_import");
    $refModel = $dsModel->getInstanceOfType("reference");
    $fldModel = $dsModel->getInstanceOfType("folder");    

    $targetReferenceModel =
      bibliograph_service_Reference::getInstance()
      ->getReferenceModel($targetDatasource);

    $targetFolderModel =
      bibliograph_service_Folder::getInstance()
      ->getFolderModel( $targetDatasource );

    $targetFolderModel->load( $targetFolderId );
    
    if( count($ids) == 0 )
    {
      $sessionId = $this->getSessionId();
      $fldModel->findWhere( array('label' => $sessionId ) );
      if($fldModel->foundNothing())
      {
        throw new JsonRpcException($this->tr("Data has been lost due to session change. Please import again."));
      }
      $fldModel->loadNext();
      //$this->debug("Import folder $sessionId has id " . $fldModel->id() );
      $refModel->findLinked( $fldModel );
    }
    else
    {
      $refModel->find(new qcl_data_db_Query( array(
        'select'    => "*",
        'where'     => "id IN (" . implode(",", $ids ) .")"
      ) ) );
    }

    while( $refModel->loadNext() )
    {
      $targetReferenceModel->create();
      $targetReferenceModel->copySharedProperties( $refModel );
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
