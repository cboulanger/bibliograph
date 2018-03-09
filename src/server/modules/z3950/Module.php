<?php

namespace app\modules\z3950;

use app\models\{
  Datasource, Role, Schema, User
};
use app\modules\z3950\lib\yaz\YAZ;
use app\modules\z3950\lib\yaz\YazException;
use app\modules\z3950\models\Datasource as Z3950Datasource;
use app\modules\z3950\models\Search;
use lib\dialog\ServerProgress;
use Yii;

/**
 * z3950 module definition class
 */
class Module extends \lib\Module
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
   * Installs the plugin.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return boolean
   * @throws \Throwable
   * @throws \RuntimeException
   */
  public function install($enabled = false)
  {
    // check prerequisites
    $error = "";
    if (!function_exists("yaz_connect")) {
      $error = "Missing PHP-YAZ extension. ";
    }

    if (!class_exists("XSLTProcessor")) {
      $error .= "Missing XSL extension. ";
    }

    $xml2bib = exec(BIBUTILS_PATH . "xml2bib", $output, $return_val);
    if (!str_contains($output, "bibutils")) {
      $error .= "Could not call bibutils: $output";
    }
    if ($error !== "") {
      throw new \RuntimeException("Z39.50 module could not be initialized:" . $error);
    }

    // register datasource
    try {
      Schema::register("z3950", Schema::class);
    } catch (\lib\exceptions\RecordExistsException $e) {
      // ignore
    } catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }

    // preferences and permissions
    $app = Yii::$app;
    $this->addPreference("lastDatasource", "z3950_voyager", true);
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
      $serverDataPath = YII::$app->modules->z3950->serverDataPath;
      foreach (scandir($serverDataPath) as $file) {
        if ($file[0] == "." or !ends_with($file, ".xml")) continue;
        $path = "$serverDataPath /$file";
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
   * @throws \Exception
   * @throws \Throwable
   */
  public function createDatasources()
  {
    $manager = Yii::$app->datasourceManager;

    // Deleting old datasources
    $z3950Datasources = Datasource::find()->where(['schema' => 'z3950']);
    foreach ($z3950Datasources as $datasource) {
      $namedId = $datasource->namedId;
      Yii::debug("Deleting Z39.50 datasource '$namedId'...", "z3950");
      $manager->delete($namedId);
    }

    // Adding new datasources from XML files
    foreach ($this->getExplainFileList() as $database => $filepath) {
      $datasourceName = "z3950_" . $database;
      $explainDoc = simplexml_load_file($filepath);
      $title = substr((string)$explainDoc->databaseInfo->title, 0, 100);

      $datasource = $manager->create($datasourceName, "z3950");
      $datasource->setAttributes([
        'title' => $title,
        'hidden' => true, // should not show up as selectable datasource
        'resourcepath' => $filepath
      ]);
      $datasource->save();
      Yii::info("Added Z39.50 datasource '$title'", "z3950");
    }
  }

  /**
   * Called when a user logs out
   */
  public function clearSearchData(User $user)
  {
    Yii::debug("Clearing search data for {$user->name}...'");
    /** @var Z3950Datasource[] $z3950Datasources */
    $z3950Datasources = Datasource::find()->where(['schema' => 'z3950']);
    // delete all search records of this user in all of the z39.50 caches
    foreach ($z3950Datasources as $datasource) {
      $hits = Search::deleteAll(["UserId" => $user->id]);
      if ($hits) {
        Yii::info("Deleted $hits search records of user '{$user->name}' in '$datasource'.", "z3950");
      }
    }
  }

  /**
   * Returns the query as a string constructed from the
   * query data object
   * @param object $queryData
   * @return string
   */
  protected function getQueryString($queryData)
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
  protected function fixQueryString($query)
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
   * Configures the yaz object for a ccl query with a minimal common set of fields:
   * title, author, keywords, year, isbn, all
   * @param YAZ $yaz
   * @return void
   */
  protected function configureCcl(YAZ $yaz)
  {
    $yaz->ccl_configure(array(
      "title" => "1=4",
      "author" => "1=1004",
      "keywords" => "1=21",
      "year" => "1=31",
      "isbn" => "1=7",
      "all" => "1=1016"
    ));
  }

  /**
   * Does the actual work of executing the Z3950 request on the remote server.
   *
   * @param Z3950Datasource $datasource
   * @param $query
   * @param ServerProgress|null $progressBar
   *    A progressbar object responsible for displaying the progress
   *    on the client (optional)
   * @throws YazException
   * @return void
   */
  function executeZ3950Request(Z3950Datasource $datasource, $query, ServerProgress $progressBar = null)
  {
    // remember last datasource used
    $this->setPreference("lastDatasource", $datasource->namedId);
    $query = $this->fixQueryString($query);

    Yii::debug("Executing query '$query' on remote Z39.50 database '$datasource' ...", "z3950");

    $yaz = new YAZ($datasource->resourcepath);
    $yaz->connect();
    $this->configureCcl($yaz);

    try {
      $yaz->search(new YAZ_CclQuery($query));
    } catch (YazException $e) {
      throw new qcl_server_ServiceException($this->tr("The server does not understand the query \"%s\". Please try a different query.", $query));
    }

    try {
      $syntax = $yaz->setPreferredSyntax(array("marc"));
      $this->log("Syntax is '$syntax' ...", BIBLIOGRAPH_LOG_Z3950);
    } catch (YazException $e) {
      throw new qcl_server_ServiceException($this->tr("Server does not support a convertable format."));
    }

    if ($progressBar) $progressBar->setProgress(0, $this->tr("Waiting for remote server..."));

    /*
     * Result
     */
    $yaz->wait();

    $error = $yaz->getError();
    if ($error) {
      $this->log("Server error (yaz_wait): $error. Aborting.", BIBLIOGRAPH_LOG_Z3950);
      throw new qcl_server_ServiceException($this->tr("Server error: %s", $error));
    }

    $info = array();
    $hits = $yaz->hits($info);
    $this->log("(Optional) result information: " . json_encode($info), BIBLIOGRAPH_LOG_Z3950);

    /*
     * No result or a too large result
     */
    $maxHits = 1000; // @todo make this configurable
    if ($hits == 0) {
      throw new qcl_server_ServiceException($this->tr("No results."));
    } elseif ($hits > $maxHits) {
      throw new qcl_server_ServiceException($this->tr("The number of results is higher than %s records. Please narrow down your search.", $maxHits));
    }


    /*
     * save search results
     */
    $this->log("Found $hits records...", BIBLIOGRAPH_LOG_Z3950);

    try {
      $searchModel->loadWhere(array('query' => $query));
      $searchModel->delete();
      $this->log("Deleted existing search data for query '$query'.", BIBLIOGRAPH_LOG_Z3950);
    } catch (qcl_data_model_RecordNotFoundException $e) {
    }

    $activeUserId = $this->getApplication()->getAccessController()->getActiveUser()->id();
    $searchId = $searchModel->create(array(
      'query' => $query,
      'hits' => $hits,
      'UserId' => $activeUserId
    ));
    $this->log("Created new search record #$searchId for query '$query' for user #$activeUserId.", BIBLIOGRAPH_LOG_Z3950);

    if ($progressBar) $progressBar->setProgress(10, $this->tr("%s records found.", $hits));

    /*
     * Retrieve record data
     */
    $this->log("Getting row data from remote Z39.50 database ...", BIBLIOGRAPH_LOG_Z3950);
    $yaz->setRange(1, $hits);
    $yaz->present();

    $error = $yaz->getError();
    if ($error) {
      $this->log("Server error (yaz_present): $error. Aborting.", BIBLIOGRAPH_LOG_Z3950);
      throw new qcl_server_ServiceException($this->tr("Server error: $error."));
    }

    $result = new YAZ_MarcXmlResult($yaz);

    for ($i = 1; $i <= $hits; $i++) {
      try {
        $result->addRecord($i);
        if ($progressBar)
          $progressBar->setProgress(10 + (($i / $hits) * 80), $this->tr("Retrieving %s of %s records...", $i, $hits));
      } catch (YazException $e) {
        if (stristr($e->getMessage(), "timeout")) {
          throw new qcl_server_ServiceException($this->tr("Server timeout trying to retrieve %s records: try a more narrow search", $hits));
        }
        throw new qcl_server_ServiceException($this->tr("Server error: %s.", $e->getMessage()));
      }
    }

    // visually separate verbose output for debugging
    function ml($description, $text)
    {
      $hl = "\n" . str_repeat("-", 100) . "\n";
      return $hl . $description . $hl . $text . $hl;
    }

    $this->log(ml("XML", $result->getXml()), BIBLIOGRAPH_LOG_Z3950_VERBOSE);
    $this->log("Formatting data...", BIBLIOGRAPH_LOG_Z3950);

    if ($progressBar) $progressBar->setProgress(90, $this->tr("Formatting records..."));

    /*
     * convert to MODS
     */
    $mods = $result->toMods();
    $this->log(ml("MODS", $mods), BIBLIOGRAPH_LOG_Z3950_VERBOSE);

    /*
     * convert to bibtex and fix some issues
     */
    $xml2bib = new qcl_util_system_Executable(BIBUTILS_PATH . "xml2bib");
    $bibtex = $xml2bib->call("-nl -fc -o unicode", $mods);
    $bibtex = str_replace("\nand ", "; ", $bibtex);
    $this->log(ml("BibTeX", $bibtex), BIBLIOGRAPH_LOG_Z3950_VERBOSE);

    /*
     * convert to array
     */
    $parser = new BibtexParser;
    $records = $parser->parse($bibtex);

    if (count($records) === 0) {
      $this->log("Empty result set, aborting...", BIBLIOGRAPH_LOG_Z3950);
      throw new qcl_server_ServiceException($this->tr("Cannot convert server response"));
    }

    /*
     * saving to local cache
     */
    $this->log("Saving data...", BIBLIOGRAPH_LOG_Z3950);

    $firstRecordId = 0;
    //$rowData = array();

    $step = 10 / count($records);
    $i = 0;

    foreach ($records as $item) {
      if ($progressBar) $progressBar->setProgress(90 + ($step * $i++), $this->tr("Caching records..."));

      $p = $item->getProperties();

      // fix bibtex issues
      foreach (array("author", "editor") as $key) {
        $p[$key] = str_replace("{", "", $p[$key]);
        $p[$key] = str_replace("}", "", $p[$key]);
      }

      /*
       * create record
       */
      $id = $recordModel->create($p);
      if (!$firstRecordId) $firstRecordId = $id;

      $recordModel->set(array(
        'citekey' => $item->getItemID(),
        'reftype' => $item->getItemType()
      ));
      $recordModel->save();
      $recordModel->linkModel($searchModel);
      $this->log(ml("Model Data", print_r($recordModel->data(), true)), BIBLIOGRAPH_LOG_Z3950_VERBOSE);
    }

    $lastRecordId = $id;
    $firstRow = 0;
    $lastRow = $hits - 1;

    $data = array(
      'firstRow' => $firstRow,
      'lastRow' => $lastRow,
      'firstRecordId' => $firstRecordId,
      'lastRecordId' => $lastRecordId
    );
    $searchId = $resultModel->create($data);
    $this->log("Saved result data for search #$searchId, rows $firstRow-$lastRow...", BIBLIOGRAPH_LOG_Z3950);

    $resultModel->linkModel($searchModel);

  }
}
