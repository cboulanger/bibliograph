<?php

namespace app\modules\z3950;

use lib\util\Executable;
use Yii;
use app\models\{
  Datasource, Role, Schema, User
};
use app\modules\z3950\models\{
  Datasource as Z3950Datasource, Search
};
use Exception;
use lib\exceptions\RecordExistsException;
use yii\web\UserEvent;

/**
 * z3950 module definition class
 * @property Z3950Datasource[] $datasources
 */
class Module extends \lib\Module
{
  /**
   * The version of the module
   * @var string
   */
  protected $version = "1.0.5";

  /**
   * A string constant defining the category for logging and translation
   */
  const CATEGORY="z3950";

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
   * Module constructor.
   * @param string $id
   * @param $parent
   * @param array $config
   */
  public function __construct(string $id, $parent = null, array $config = [])
  {
    parent::__construct($id, $parent, $config);
    if (!function_exists("yaz_connect")) {
      $this->disabled = true;
      Yii::warning("YAZ not enabled, disabled Z39.50 module.");
      //Yii::debug(php_ini_loaded_file()  . " and " . php_ini_scanned_files()) ;
    }
  }

  /**
   * Installs the plugin.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return boolean
   * @throws Exception
   */
  public function install($enabled = false)
  {
    // check prerequisites
    if (!function_exists("yaz_connect")) {
      $this->errors[] = "Missing PHP-YAZ extension. ";
    }

    if (!class_exists("XSLTProcessor")) {
      $this->errors[] = "Missing XSL extension. ";
    }

    if (!class_exists('\\app\\modules\\bibutils\\Module')) {
      $this->errors[] = "You need to install & activate the bibutils module.";
    } else {
      $xml2bib = \app\modules\bibutils\Module::createCmd("xml2bib");
      try{
        $xml2bib->call("--version");
        if ( !$xml2bib->getStdErr() || !str_contains( $xml2bib->getStdErr(), "bibutils")) {
          Yii::warning("Unexpected output of xml2bib --version:" . $xml2bib->getStdErr());
          $this->errors[] = "Could not call bibutils.";
        }
      } catch ( Exception $e){
        Yii::warning("Error calling xml2bib --version:" . $e->getMessage());
        $this->errors[] = "Could not call bibutils.";
      }
    }

    if ( count ( $this->errors )) {
      return false;
    }

    // register datasource
    try {
      Schema::register("z3950", Z3950Datasource::class);
    } catch (RecordExistsException $e) {
      // ignore
    }

    // preferences and permissions
    $app = Yii::$app;
    $this->addPreference("lastDatasource", "z3950_voyager", true);
    try {
      $app->accessManager->addPermissions(["z3950.manage"], [
        Role::findByNamedId("admin")
      ]);
    } catch (\Exception $e) {
      //ignore
    }
    // create datasources
    $this->createDatasources();

    // register module
    return parent::install(true);
  }

  /**
   * Returns an associative array, keys being the names of the Z39.50 databases, values the
   * paths to the xml EXPLAIN files
   * @return array
   */
  protected function getExplainFileList()
  {
    static $data = null;
    if ($data === null) {
      $data = array();
      $serverDataPath = $this->serverDataPath;
      foreach (scandir($serverDataPath) as $file) {
        if ($file[0] == "." or !ends_with($file, ".xml")) continue;
        $path = "$serverDataPath/$file";
        $explain = simplexml_load_file($path);
        $serverInfo = $explain->serverInfo;
        $database = (string)$serverInfo->database;
        $data[$database] = $path;
      }
    }
    return $data;
  }

  /**
   * Create bibliograph datasources from Z39.50 explain files
   * @throws Exception
   */
  public function createDatasources()
  {
    $manager = Yii::$app->datasourceManager;

    // Deleting old datasources
    $this->dropAllZ3950Tables();

    // Adding new datasources from XML files
    foreach ($this->getExplainFileList() as $database => $filepath) {
      $datasourceName = "z3950_" . $database;
      $explainDoc = simplexml_load_file($filepath);
      $title = substr((string)$explainDoc->databaseInfo->title, 0, 100);

      $datasource = $manager->create($datasourceName, "z3950");
      $datasource->setAttributes([
        'title' => $title,
        'hidden' => 1, // should not show up as selectable datasource
        'resourcepath' => $filepath,
      ]);
      $datasource->save();
      Yii::info("Added Z39.50 datasource '$title'", self::CATEGORY);
    }
  }

  /**
   * Returns the query as a string constructed from the
   * query data object
   * @param object $queryData
   * @return string
   */
  public function getQueryString($queryData)
  {
    $query = $queryData->query->cql;
    return $this->fixQueryString($query);
  }

  /**
   * Checks the query and optimizes it before
   * sending it to the remote server
   * @param $query
   * @return string
   */
  public function fixQueryString($query)
  {
    // todo: identify DOI
    if (substr($query, 0, 3) == "978") {
      $query = 'isbn=' . $query;
    } elseif (!strstr($query, "=")) {
      $query = 'all="' . $query . '"';
    }
    return $query;
  }

  /**
   * Returns the named ids of the all datasources with 'z3950' schema
   * @return array
   */
  public function getDatasourceNames()
  {
    return Datasource::find()
      ->select("namedId")
      ->where(['schema' => 'z3950'])
      ->column();
  }

  /**
   * Returns all datasources that have the 'z3950' schema
   * @return Z3950Datasource[]
   */
  public function getDatasources()
  {
    return Datasource::find()
      ->select("namedId")
      ->where(['schema' => 'z3950'])
      ->all();
  }

  /**
   * Called when a user logs out
   * @param User $user
   */
  public function clearSearchData(User $user)
  {
    if( count($this->datasources )){
      try{
        $datasource = Datasource::getInstanceFor($this->datasources[0]->namedId);
        Search::setDatasource($datasource);
        $searches = Search::find()->where(['UserId'=>$user->id])->all();
        foreach ($searches as $search) $search->delete();
        Yii::debug("Deleted search data.",self::CATEGORY, __METHOD__);
      } catch (\Error $e) {
        Yii::error($e->getMessage());
      }
    }
  }

  /**
   * Deletes all tables that belong to this module (i.e., start with z3950)
   */
  public function dropAllZ3950Tables()
  {
    $z3950Datasources = $this->getDatasourceNames();
    foreach ($z3950Datasources as $namedId) {
      Yii::debug("Deleting Z39.50 datasource '$namedId'...", self::CATEGORY, __METHOD__);
      try {
        Yii::$app->datasourceManager->delete($namedId, true);
      } catch (Exception $e) {
        Yii::error("Problem deleting datasource ‘$namedId‘:" . $e, self::CATEGORY );
      }
    }
  }

  /**
   * Event handler for logout event
   * @param UserEvent $e
   */
  public static function on_after_logout ( UserEvent $e) {
    /** @var \app\models\User|null $user */
    $user = $e->identity;
    if( ! $user ) return;
    /** @var Module $module */
    $module = \Yii::$app->getModule('z3950');
    try{
      $module->clearSearchData($user);
    } catch (\Throwable $e){
      \Yii::warning($e->getMessage());
    }
  }

  /**
   * Event handler for delete event
   * @param \yii\base\Event $e
   */
  public static function on_after_delete ( \yii\base\Event $e) {
    /** @var \app\models\User|null $user */
    $user = $e->sender;
    if( ! $user ) return;
    /** @var Module $module */
    $module = \Yii::$app->getModule('z3950');
    try{
      $module->clearSearchData($user);
    } catch (\Throwable $e){
      \Yii::warning($e->getMessage());
    }
  }


}
