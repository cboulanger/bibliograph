<?php

namespace app\modules\z3950;

use app\models\{
  Datasource, Role, Schema, User
};
use app\modules\z3950\lib\yaz\{
  CclQuery, MarcXmlResult, YAZ, YazException
};
use app\modules\z3950\models\{
  Datasource as Z3950Datasource, Record, Result, Search
};
use Exception;
use lib\bibtex\BibtexParser;
use lib\dialog\ServerProgress;
use lib\exceptions\RecordExistsException;
use lib\exceptions\UserErrorException;
use lib\util\Executable;
use RuntimeException;
use Yii;


/**
 * z3950 module definition class
 */
class Module extends \lib\Module
{

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
   * Must have a trailing slash.
   * @var string
   */
  public $serverDataPath = __DIR__ . '/data/servers/';


  /**
   * Installs the plugin.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return boolean
   * @throws RuntimeException
   * @throws Exception
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

    exec(BIBUTILS_PATH . "xml2bib", $output, $return_val);
    if (!str_contains($output, "bibutils")) {
      $error .= "Could not call bibutils: $output";
    }
    if ($error !== "") {
      throw new RuntimeException("Z39.50 module could not be initialized:" . $error);
    }

    // register datasource
    try {
      Schema::register("z3950", Schema::class);
    } catch (RecordExistsException $e) {
      // ignore
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
      $serverDataPath = $this->serverDataPath;
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
   * @throws Exception
   */
  public function createDatasources()
  {
    $manager = Yii::$app->datasourceManager;

    // Deleting old datasources
    $z3950Datasources = Datasource::find()->where(['schema' => 'z3950']);
    foreach ($z3950Datasources as $datasource) {
      $namedId = $datasource->namedId;
      Yii::debug("Deleting Z39.50 datasource '$namedId'...", self::CATEGORY);
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
      Yii::info("Added Z39.50 datasource '$title'", self::CATEGORY);
    }
  }

  /**
   * Called when a user logs out
   * @param User $user
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
        Yii::info(
          "Deleted $hits search records of user '{$user->name}' in '$datasource'.",
          self::CATEGORY
        );
      }
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
   * @return void
   * @throws yii\db\Exception
   * @throws UserErrorException
   * @throws Exception
   */
  function sendRequest(Z3950Datasource $datasource, $query, ServerProgress $progressBar = null)
  {
    // remember last datasource used
    $this->setPreference("lastDatasource", $datasource->namedId);
    $query = $this->fixQueryString($query);

    Yii::debug("Executing query '$query' on remote Z39.50 database '$datasource' ...", self::CATEGORY);

    $yaz = new YAZ($datasource->resourcepath);
    try {
      $yaz->connect();
    } catch (YazException $e) {
      throw new UserErrorException(
        Yii::t('z3950', "Cannot connect to server: '{error}'.", [ 'error' => $yaz->getError() ] ),
        null, $e
      );
    }
    $this->configureCcl($yaz);
    $ccl = new CclQuery($query);

    try {
      $ccl->toRpn($yaz);
    } catch (YazException $e) {
      throw new UserErrorException(
        Yii::t('z3950', "Invalid query '{query}'", [ 'query' => $query ] ), null, $e
      );
    }

    try {
      $yaz->search($ccl);
    } catch (YazException $e) {
      throw new UserErrorException(
        Yii::t('z3950',
          "The server does not understand the query '{query}'. Please try a different query.",
          [ 'query' => $query ]
        ), null, $e
      );
    }

    try {
      $syntax = $yaz->setPreferredSyntax(["marc"]);
      Yii::debug("Syntax is '$syntax' ...",self::CATEGORY);
    } catch (YazException $e) {
      throw new UserErrorException(Yii::t(self::CATEGORY, "Server does not support a convertable format."));
    }

    if ($progressBar) {
      $progressBar->setProgress(0, Yii::t(self::CATEGORY, "Waiting for remote server..."));
    }

    // Result
    $yaz->wait();

    $error = $yaz->getError();
    if ($error) {
      Yii::debug("Server error (yaz_wait): $error. Aborting.", self::CATEGORY);
      throw new UserErrorException(Yii::t(self::CATEGORY, "Server error: %s", $error));
    }

    $info = [];
    $hits = $yaz->hits($info);
    Yii::debug("(Optional) result information: " . json_encode($info), self::CATEGORY);

    // No result or a too large result
    $maxHits = 1000; // @todo make this configurable
    if ($hits == 0) {
      throw new UserErrorException(Yii::t(self::CATEGORY, "No results."));
    } elseif ($hits > $maxHits) {
      throw new UserErrorException(Yii::t(
        self::CATEGORY,
        "The number of results is higher than {number} records. Please narrow down your search.",
        [ 'number' => $maxHits ]
      ));
    }
    Yii::debug("Found $hits records...", self::CATEGORY);

    // delete existing search
    $userId = Yii::$app->user->identity->getId();
    Yii::debug("Deleting existing search data for query '$query'...", self::CATEGORY);
    /** @var Search[] $searches */
    $searches = (array) Search::find()->where(['query' => $query, 'UserId' => $userId ])->all();
    foreach ($searches as $search) {
      try {
        $search->delete();
      } catch (\Throwable $e) {
        Yii::debug($e->getMessage(),self::CATEGORY);
      }
    }
    // create new search
    $search = new Search([
      'query' => $query,
      'hits' => $hits,
      'UserId' => $userId
    ]);
    $search->save();

    Yii::debug("Created new search record for query '$query' for user #$userId.", self::CATEGORY);

    if ($progressBar) {
      $progressBar->setProgress(10, Yii::t(
        self::CATEGORY, "{number} records found.", 
        ['number'=>$hits]
      ));
    }

    // Retrieve record data
    Yii::debug("Getting row data from remote Z39.50 database ...", self::CATEGORY);
    $yaz->setRange(1, $hits);
    $yaz->present();
    $error = $yaz->getError();
    if ($error) {
      Yii::debug("Server error (yaz_present): $error. Aborting.", self::CATEGORY);
      throw new UserErrorException(Yii::t(self::CATEGORY, "Server error: $error."));
    }

    // Retrieve as MARC XML
    $result = new MarcXmlResult($yaz);
    for ($i = 1; $i <= $hits; $i++) {
      try {
        $result->addRecord($i);
        if ($progressBar){
          $progressBar->setProgress(10 + (($i / $hits) * 80),
            Yii::t( self::CATEGORY,"Retrieving {index} of {number} records...",   [ 'index' =>$i, 'number' => $hits])
          );
        }
      } catch (YazException $e) {
        if (stristr($e->getMessage(), "timeout")) {
          throw new UserErrorException(
            Yii::t( self::CATEGORY,
              "Server timeout trying to retrieve {number} records: try a more narrow search",
              ['number'=>$hits]
            )
          );
        }
        throw new UserErrorException(
          Yii::t(self::CATEGORY,"Server error: {error}.", ['error' => $e->getMessage()])
        );
      }
    }

    // visually separate verbose output for debugging
    function ml($description, $text)
    {
      $hl = "\n" . str_repeat("-", 100) . "\n";
      return $hl . $description . $hl . $text . $hl;
    }

    Yii::debug(ml("XML", $result->getXml()), self::CATEGORY);
    Yii::debug("Formatting data...", self::CATEGORY);

    if ($progressBar) {
      $progressBar->setProgress(90, Yii::t(self::CATEGORY, "Formatting records..."));
    }

    // convert to MODS
    $mods = $result->toMods();
    Yii::debug(ml("MODS", $mods), self::CATEGORY);

    // convert to bibtex and fix some issues
    $xml2bib = new Executable(BIBUTILS_PATH . "xml2bib");
    $bibtex = $xml2bib->call("-nl -fc -o unicode", $mods);

    $bibtex = str_replace("\nand ", "; ", $bibtex);
    Yii::debug(ml("BibTeX", $bibtex), self::CATEGORY);

    // convert to array
    $parser = new BibtexParser();
    $records = $parser->parse($bibtex);

    if (count($records) === 0) {
      Yii::debug("Empty result set, aborting...", self::CATEGORY);
      throw new UserErrorException(Yii::t(self::CATEGORY, "Cannot convert server response"));
    }

    // saving to local cache
    Yii::debug("Saving data...", self::CATEGORY);

    $firstRecordId = 0;
    $step = 10 / count($records);
    $i = 0; $id= 0;

    foreach ($records as $item) {
      if ($progressBar) {
        $progressBar->setProgress(
          round (90 + ($step * $i++)),
          Yii::t(self::CATEGORY, "Caching records...")
        );
      }

      $p = $item->getProperties();

      // fix bibtex issues
      foreach (array("author", "editor") as $key) {
        $p[$key] = str_replace("{", "", $p[$key]);
        $p[$key] = str_replace("}", "", $p[$key]);
      }

      // create record
      $dbRecord = new Record($p);
      $dbRecord->setAttributes([
        'citekey' => $item->getItemID(),
        'reftype' => $item->getItemType(),
        'SearchId' => $search->id
      ]);
      $dbRecord->save();
      $id = $dbRecord->id;
      if (!$firstRecordId) $firstRecordId = $id;
      Yii::debug(ml("Model Data", print_r($dbRecord->getAttributes(), true)), self::CATEGORY);
    }

    $lastRecordId = $id;
    $firstRow = 0;
    $lastRow = $hits - 1;

    $data = [
      'firstRow' => $firstRow,
      'lastRow' => $lastRow,
      'firstRecordId' => $firstRecordId,
      'lastRecordId' => $lastRecordId,
      'SearchId' => $search->id
     ];
    $result = new Result($data);
    $result->save();
    Yii::debug("Saved result data for search #{$search->id}, rows $firstRow-$lastRow...", self::CATEGORY);
  }

  /**
   * @return string
   * @throws YazException
   * @throws Exception
   */
  public function test()
  {
    $gbvpath = $this->module->serverDataPath . "z3950.gbv.de-20010-GVK-de.xml";

    $yaz = new Yaz( $gbvpath );
    $yaz->connect();

    $yaz->ccl_configure(array(
      "title"     => "1=4",
      "author"    => "1=1004",
      "keywords"  => "1=21",
      "year"      => "1=31"
    ) );

    $query = new CclQuery("author=boulanger");
    $yaz->search( $query );
    $yaz->wait();
    $hits = $yaz->hits();

    Yii::info( "$hits hits.");

    $yaz->setSyntax("USmarc");
    $yaz->setElementSet("F");
    $yaz->setRange( 1, 3 );
    $yaz->present();

    $result = new MarcXmlResult($yaz);

    for( $i=1; $i<3; $i++)
    {
      $result->addRecord( $i );
    }
    $mods = $result->toMods();

    $xml2bib = new Executable("xml2bib");
    $bibtex = $xml2bib->call("-nl -b -o unicode", $mods );
    $parser = new BibtexParser;

    Yii::info( $parser->parse( $bibtex ) );
    return "OK";
  }
}
