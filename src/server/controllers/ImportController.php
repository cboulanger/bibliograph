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

use app\models\Datasource;
use app\models\LastFileUpload;
use app\models\Folder;
use app\models\Reference;
use app\models\Session;
use app\models\ImportFormat;
use app\modules\converters\import\AbstractParser;
use lib\exceptions\UserErrorException;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use Yii;

/**
 *
 */
class ImportController extends AppController
{
  use traits\TableTrait;

  /**
   * @var string The name of the datasource which is used for importing
   */
  protected $datasource = "bibliograph_import";

  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param $datasource
   */
  public function actionTableLayout( $datasource )
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
        'relation' => [
          'name' => "folders",
          'foreignId' => 'FolderId'
        ],
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
    $listData = [[
      'value' => null,
      'label' => Yii::t('app', "2. Choose import format" ),
      'description' => "",
    ]];
    /** @var ImportFormat $format */
    foreach( $importFormats as $format ){
      $listData[] = [
        'value' => $format->namedId,
        'label' => $format->name,
        'description' => $format->description
      ];
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
   * @throws \JsonRpc2\Exception
   */
  public function actionParseUpload( string $format )
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
      $file = LastFileUpload::instance();
    } catch (\RuntimeException $e){
      throw new UserErrorException("No file was uploaded");
    }

    $givenExtension = $file->extension;
    $allowedExtensions = $importFormatModel->getExtensions();
    if( ! in_array( $givenExtension,$allowedExtensions ) )
    {
      throw new UserErrorException(
        Yii::t('app',
          "Format '{format}' expects file extension(s) '{allowedExtensions}'. The file you uploaded has extension '{givenExtension}'.",[
            'format'            => $format,
            'allowedExtensions' => implode("', '", $allowedExtensions),
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
        'parentId' => 0,
        'position' => 0
      ]);
      try {
        $folder->save();
      } catch (Exception $e) {
        throw new UserErrorException($e->getMessage());
      }
    }
    
    // convert and import data
    $data = file_get_contents( $file->path );
    $file->delete();
    
    $parserClass = $importFormatModel->class;
    if( ! class_exists($parserClass) ){
      throw new UserErrorException("Importer class '$parserClass' does not exist!");
    }
    /** @var AbstractParser $parser */
    $parser = new $parserClass();
    $records = $parser->parse( $data );
    foreach( $records as $record )
    {
      $referenceClass = Datasource::in($this->datasource,"reference");
      /** @var Reference $reference */
      $reference = new $referenceClass();
      $reference->setAttributes( $record );
      if( ! $reference->citekey ){
        $reference->citekey = $reference->computeCitekey();
      }
      try {
        $reference->save();
      } catch (Exception $e) {
        Yii::warning($e->getMessage());
      }
      $reference->link("folders", $folder );
    }

    // return information on containing folder
    return [
      'folderId'   => $folder->id,
      'datasource' => $this->datasource
    ];
  }

  /**
   * Imports the references with the given ids to a target folder
   * @param string $ids Comma-separated ids
   * @param string $targetDatasource
   * @param int $targetFolderId
   * @return string Diagnostic message
   * @throws \JsonRpc2\Exception
   */
  public function actionImport( string $ids, string $targetDatasource, int $targetFolderId )
  {
    $this->requirePermission("reference.import");
    $ids = explode(',',$ids);
    $refs = $this->findIn($this->datasource,"reference")
      ->where(['in','id',$ids])
      ->all();

    $targetReferenceClass = Datasource::in($targetDatasource,"reference");
    /** @var Folder $targetFolder */
    $targetFolder = $this->findIn($targetDatasource,"folder")
      ->where(['id'=>$targetFolderId])
      ->one();
    if( ! $targetFolder){
      throw new UserErrorException("The target folder #$targetFolderId does not exist.");
    }

    try {
      $commonAttributes = array_intersect(
        array_keys(Reference::getTableSchema()->columns),
        array_keys($targetReferenceClass::getTableSchema()->columns)
      );
    } catch (InvalidConfigException $e) {
      Yii::error($e);
      throw new UserErrorException($e->getMessage());
    }
    $count = 0;
    /** @var Reference $ref */
    foreach ($refs as $ref) {
      /** @var Reference $importedReference */
       $importedReference = new $targetReferenceClass();
       $importedReference->setAttributes(
         $ref->getAttributes($commonAttributes)
       );
      try {
        $importedReference->save();
        $importedReference->link("folders", $targetFolder );
        $count++;
      } catch (Exception $e) {
        Yii::warning($e->getMessage());
      }
    }
    // update child count and reload folders
    $targetFolder->getChildCount(true);
    $this->dispatchClientMessage("folder.reload", array(
      'datasource'  => $targetDatasource,
      'folderId'    => $targetFolderId
    ) );

    return "$count references imported.";
  }
}
