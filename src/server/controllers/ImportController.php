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

use app\models\FileUpload;
use app\models\Folder;
use app\models\Reference;
use app\models\Session;
use lib\exceptions\UserErrorException;
use Yii;

use app\controllers\AppController;
use app\models\ImportFormat;
use yii\db\Exception;
use yii\db\StaleObjectException;

/**
 *
 */
class ImportController extends AppController
{

  /**
   * @var string The name of the datasource which is used for importing
   */
  protected $datasource = "bibliograph_datasource";

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
   * @return array
   */
  public function actionGetTableLayout( $datasource )
  {
    return [
      'columnLayout' => [
        'id' => [
          'header'  => "ID",
          'width'   => 50,
          'visible' => false
        ],
        'author' => [
          'header'  => Yii::t('app', "Author"),
          'width'   => "1*"
        ],
        'year' => [
          'header'  => Yii::t('app', "Year"),
          'width'   => 50
        ],
        'title' => [
          'header'  => Yii::t('app', "Title"),
          'width'   => "3*"
        ]
      ],
      'queryData' => [
        'orderBy' => "author,year,title",
        'link'    => ['relation' => "Folder_Reference"],
      ],
      'addItems' => null
    ];
  }

  /**
   * Shorthand method
   * @return \yii\db\ActiveQuery
   */
  protected function findInFolders() {
    return $this->findIn($this->datasource,"folder");
  }

  /*
  ---------------------------------------------------------------------------
    SERVICES
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the list of import formats for a selectbox
   */
  public function actionImportFormats()
  {
    $importFormats = ImportFormat::find()->where(['active' => 1])->orderBy("name")->all();
    $listData = array( array(
      'value' => null,
      'label' => Yii::t('app', "2. Choose import format" )
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
   * Parse the data from the last uploaded file with the given format.
   * Returns an associative array containing the keys "datasource" with the name of the
   * datasource (usually "bibliograph_import") and "folderId" containing
   * the numeric value of the folder containing the processed references.
   *
   * @param string $format
   *    The name of the import format
   * @throws UserErrorException
   * @return array
   * @throws \JsonRpc2\Exception
   */
  public function actionParseUpload(string $format )
  {
    $this->requirePermission("reference.import");

    // load importer object according to format
    /** @var ImportFormat $importFormatModel */
    $importFormatModel = ImportFormat::findByNamedId($format);
    if(! $importFormatModel ){
      throw new UserErrorException( Yii::t('app',
        "Unknown format '{format}'.", [ 'format' => $format]
      ));
    }
    try{
      $file = FileUpload::getLastUploadPath();
    } catch (\RuntimeException $e){
      throw new UserErrorException($e->getMessage());
    }

    $givenExtension = pathinfo( $file, PATHINFO_EXTENSION);
    $allowedExtensions = $importFormatModel->getExtensions();
    if( ! in_array( $givenExtension,$allowedExtensions ) )
    {
      throw new UserErrorException(
        Yii::t('app',
          "Format '{format}' expects file extension(s) '{allowedExtensions}'. The file you uploaded has extension '{givenExtension}.'",[
            'format'            => $format,
            'allowedExtensions' => $allowedExtensions,
            'givenExtension'    => $givenExtension
          ])
      );
    }

    // cleanup unused data: purge all folders with names of sessions that no longer exist
    /** @var Folder $folder */
    foreach ($this->findInFolders()->all() as $folder)
    {
      if ( ! Session::find()->where( ['id' => $folder->label ])->exists() ){
        foreach( $folder->getReferences()->all() as $reference ){
          try {
            $reference->delete();
          } catch (\Throwable $e) {
            Yii::warning($e->getMessage());
          }
        }
        try {
          $folder->delete();
        } catch (\Throwable $e) {
          Yii::warning($e->getMessage());
        }
      }
    }
    
    // empty an existing folder with the session id as label
    $sessionId = Yii::$app->session->getId();
    /** @var Folder|null $folder */
    $folder = $this->findInFolders()
      ->where(['id'=>$sessionId])
      ->one();
    if( $folder ){
      /** @var Reference $reference */
      foreach ($folder->getReferences() as $reference) {
        try {
          $reference->delete();
        } catch (\Throwable $e) {
          Yii::warning($e->getMessage());
        }
      }
    } else {
      $folder = new Folder([
        'label'    => $sessionId,
        'parentId' => 0
      ]);
      try {
        $folder->save();
      } catch (Exception $e) {
        throw new UserErrorException($e->getMessage());
      }
    }
    
    // convert and import data
    $data = file_get_contents( $file );
    
    $parserClass = $importFormatModel->class;
    if( ! class_exists($parserClass) ){
      throw new UserErrorException("Importer class '$parserClass' does not exist!");
    }
    $parser = new $parserClass([

    ]);
    $records = $parser->parse( $data );
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
        throw new UserErrorException(Yii::t('app',"Data has been lost due to session change. Please import again."));
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
    
    $xml2bib = new qcl_util_system_Executable( BIBUTILS_PATH . "xml2bib");
    $xml2bib->call( "-v" );
    Yii::info( "stdout: " . $xml2bib->getStdOut() );
    Yii::info( "stderr: " . $xml2bib->getStdErr() );
  }
}
