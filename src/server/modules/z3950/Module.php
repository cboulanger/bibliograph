<?php

namespace app\modules\z3950;
use app\models\Datasource;
use app\models\Role;
use app\models\Schema;
use app\models\User;
use app\modules\z3950\models\Search;
use Yii;
use yii\base\Event;
use yii\base\ModelEvent;
use yii\web\UserEvent;

/**
 * z3950 module definition class
 */
class Module extends \yii\base\Module
{

  /**
   * @inheritdoc
   */
  public $controllerNamespace = 'app\modules\z3950\controllers';

  /**
   * The path to the directory containing the Z39.50 "Explain" xml files
   * @var string
   */
  public $serverDataPath = __DIR__ . '/data/servers';

  /**
   * @inheritdoc
   */
  public function init()
  {
    parent::init();

    $config = Yii::$app->config;
    $key =  "plugins.z3950.enabled";
    if( ! $config->keyExists($key) ){
      $config->addPreference($key,false );
      try{
        $this->install();
      } catch ( \RuntimeException $e ){
        // unsuccessful, fail silently
        Yii::error($e->getMessage());
        return;
      }
      //

      // register logout handler
      Yii::$app->user->on(\yii\web\User::EVENT_AFTER_LOGOUT, function ( UserEvent $e) {
        $this->clearSearchData($e->identity);
      });
      Yii::$app->on(BaseActiveRecord::EVENT_AFTER_DELETE, function( ModelEvent $e){
        if( $e->sender instanceof User);
        $this->clearSearchData($e->sender);
      });
    }
  }

  /**
   * Installs the plugin.
   * @return void
   * @throws \Throwable
   */
  public function install()
  {
    // check prerequisites
    $error = "";
    if (  ! function_exists("yaz_connect" ) )
    {
      $error = "Missing PHP-YAZ extension. ";
    }

    if ( ! class_exists( "XSLTProcessor" ) )
    {
      $error .= "Missing XSL extension. ";
    }

    $xml2bib = exec( BIBUTILS_PATH . "xml2bib", $output, $return_val);
    if ( ! str_contains( $output, "bibutils" ) ) {
      $error .= "Could not call bibutils: $output";
    }
    if ( $error !== "" ){
      throw new \RuntimeException("Z39.50 module could not be initialized:" . $error);
    }

    // register datasource
    try {
      Schema::register("z3950", Schema::class);
    } catch( \lib\exceptions\RecordExistsException $e) {
      // ignore
    } catch( \Exception $e){
      throw new \RuntimeException($e->getMessage());
    }

    // preferences and permissions
    $app = Yii::$app;
    $app->config->addPreference( "z3950.lastDatasource", "z3950_voyager", true );
    try {
      $app->accessManager->addPermissions(["z3950.manage"], [
        Role::findByNamedId("admin"),
        Role::findByNamedId("manager")
      ]);
    } catch (\yii\db\Exception $e) {
      //ignore
    }
    // create datasources
    $this->createDatasources();
  }

  /**
   * Returns an associative array, keys being the names of the Z39.50 databases, values the
   * paths to the xml EXPLAIN files
   * @return array
   */
  protected function getExplainFileList()
  {
    static $data=null;
    if( $data === null )
    {
      $data = array();
      $serverDataPath = YII::$app->modules->z3950->serverDataPath;
      foreach( scandir( $serverDataPath) as $file )
      {
        if( $file[0] == "." or ! ends_with($file, ".xml" ) ) continue;
        $path = "$serverDataPath /$file";
        $explain = simplexml_load_file( $path );
        $serverInfo = $explain->serverInfo;
        $database = (string) $serverInfo->database;
        $data[$database] = $path;
      }
    }
    return $data;
  }

  /**
   * Create bibliograph datasources from Z39.50 explain files
   * @throws \Exception
   * @throws \Throwable
   */
  public function createDatasources()
  {
    $manager = Yii::$app->datasourceManager;

    // Deleting old datasources
    $z3950Datasources = Datasource::find()->where(['schema'=>'z3950']);
    foreach ($z3950Datasources as $datasource){
      $namedId = $datasource->namedId;
      Yii::debug("Deleting Z39.50 datasource '$namedId'...", "z3950");
      $manager->delete($namedId);
    }

    // Adding new datasources from XML files
    foreach( $this->getExplainFileList() as $database => $filepath )
    {
      $datasourceName = "z3950_" . $database;
      $explainDoc = simplexml_load_file( $filepath );
      $title = substr( (string) $explainDoc->databaseInfo->title, 0, 100 );

      $datasource = $manager->create($datasourceName,"z3950");
      $datasource->setAttributes([
        'title'         => $title,
        'hidden'        => true, // should not show up as selectable datasource
        'resourcepath'  => $filepath
      ]);
      $datasource->save();
      Yii::info("Added Z39.50 datasource '$title'", "z3950");
    }
  }

  /**
   * Called when a user logs out
   */
  public function clearSearchData( User $user )
  {
    /** @var \app\modules\z3950\models\Datasource[] $z3950Datasources */
    $z3950Datasources = Datasource::find()->where(['schema'=>'z3950']);
    // delete all search records of this user in all of the z39.50 caches
    foreach ( $z3950Datasources as $datasource ){
      $hits = Search::deleteAll(["UserId" => $user->id]);
      if( $hits ){
        Yii::info("Deleted $hits search records of user '{$user->name}' in '$datasource'.", "z3950");
      }
    }
  }
}
