<?php

namespace app\modules\z3950\controllers;

use app\models\User;
use Yii;
use Exception;
use app\controllers\{ traits\AuthTrait, traits\DatasourceTrait };
use app\modules\z3950\Module;
use app\models\Datasource;
use app\modules\z3950\models\{ Record, Search, Datasource as Z3950Datasource };
use app\modules\z3950\lib\yaz\{ CclQuery, MarcXmlResult, Yaz, YazException, YazTimeoutException };
use lib\dialog\ServerProgress;
use lib\exceptions\UserErrorException;
use lib\bibtex\BibtexParser;
use lib\util\Executable;

/**
 * Class ProgressController
 * @package modules\z3950\controllers
 * @property Module $module
 */
class SearchController extends \yii\web\Controller
{
  use AuthTrait;
  use DatasourceTrait;

  protected function getNoAuthActions()
  {
    return ['index','test'];
  }

  public function actionIndex()
  {
    return "nothing here";
  }

  public function actionTest()
  {
    Yii::$app->user->login(User::findByNamedId("admin"));
    $this->actionProgress("z3950_voyager","shakespeare's english","1234");
  }

  /**
   * Executes a Z39.50 request on the remote server. Called
   * by the ServerProgress widget on the client. If server times out
   * it will retry up to three times.
   *
   * @param string $datasource The name of the datasource
   * @param string $query The cql query
   * @param string $id The id of the progress widget
   * @return void
   */
  public function actionProgress($datasource, $query, $id)
  {
    static $retries = 0;
    $progressBar = new ServerProgress($id);
    try {
      $this->sendRequest($datasource, $query, $progressBar);
      $progressBar->dispatchClientMessage("z3950.dataReady", $query);
      $progressBar->complete();
    } catch (YazTimeoutException $e) {
      // retry
      if( $retries < 4){
        $progressBar->setProgress(0, Yii::t("z3950", "Server timed out. Trying again..."));
        sleep(rand(1,3));
        $this->actionProgress($datasource, $query, $progressBar );
      } else {
        $progressBar->error(Yii::t("z3950", "Server timed out."));
      }
    } catch (UserErrorException $e) {
      $progressBar->error($e->getMessage());
    } catch (Exception $e) {
      Yii::error($e);
      $progressBar->error($e->getMessage());
    }
    Yii::$app->getResponse()->isSent = true;
  }

  /**
   * Configures the yaz object for a ccl query with a minimal common set of fields:
   * title, author, keywords, year, isbn, all
   * @param Yaz $yaz
   * @return void
   */
  protected function configureCcl(Yaz $yaz)
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
   * @param string $datasourceName
   * @param $query
   * @param ServerProgress|null $progressBar
   *    A progressbar object responsible for displaying the progress
   *    on the client (optional)
   * @return void
   * @throws YazTimeoutException
   * @throws UserErrorException
   * @throws Exception
   */
  public function sendRequest( string $datasourceName, $query, ServerProgress $progressBar = null)
  {
    $datasource = Datasource::getInstanceFor($datasourceName);
    if( ! $datasource or ! $datasource instanceof Z3950Datasource ){
      throw new \InvalidArgumentException("Invalid datasource '$datasourceName'.");
    }
    // set datasource table prefixes
    Search::setDatasource($datasource);
    Record::setDatasource($datasource);

    // remember last datasource used
    $this->module->setPreference("lastDatasource", $datasourceName );
    $query = $this->module->fixQueryString($query);

    Yii::debug("Executing query '$query' on remote Z39.50 database '$datasourceName' ...", Module::CATEGORY);

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
      Yii::debug("Syntax is '$syntax' ...",Module::CATEGORY);
    } catch (YazException $e) {
      throw new UserErrorException(Yii::t(Module::CATEGORY, "Server does not support a convertable format."));
    }

    if ($progressBar) {
      $progressBar->setProgress(0, Yii::t(Module::CATEGORY, "Waiting for remote server..."));
    }

    // @todo make configurable
    $options = [
      'timeout' => 120
    ];

    // Result
    try {
      $yaz->wait($options);
    } catch ( YazException $e) {
      Yii::debug("Server error (yaz_wait): ". $e->getMessage(), Module::CATEGORY);
      throw new UserErrorException(
        Yii::t(Module::CATEGORY, "Server error: {error}", ['error' => $e->getMessage()])
      );
    }

    $info = [];
    $hits = $yaz->hits($info);
    Yii::debug("$hits hits, additional result information: " . json_encode($info), Module::CATEGORY);

    // No result or a too large result
    $maxHits = 1000; // @todo make this configurable
    if ($hits == 0) {
      throw new UserErrorException(Yii::t(Module::CATEGORY, "No results."));
    } elseif ($hits > $maxHits) {
      throw new UserErrorException(Yii::t(
        Module::CATEGORY,
        "The number of results is higher than {number} records. Please narrow down your search.",
        [ 'number' => $maxHits ]
      ));
    }
    Yii::debug("Found $hits records...", Module::CATEGORY);

    // delete existing search
    $userId = Yii::$app->user->identity->getId();
    Yii::debug("Deleting existing search data for query '$query'...", Module::CATEGORY);
    /** @var Search[] $searches */
    $searches = (array) Search::find()->where(['query' => $query, 'UserId' => $userId ])->all();
    foreach ($searches as $search) {
      try {
        $search->delete();
      } catch (\Throwable $e) {
        Yii::debug($e->getMessage(),Module::CATEGORY);
      }
    }
    // create new search
    $search = new Search([
      'query' => $query,
      'datasource' => $datasourceName,
      'hits' => $hits,
      'UserId' => $userId
    ]);
    $search->save();
    $searchId = $search->id;
    Yii::debug("Created new search record #$searchId for query '$query' for user #$userId.", Module::CATEGORY);

    if ($progressBar) {
      $progressBar->setProgress(10, Yii::t(
        Module::CATEGORY, "Found {number} records. Please wait...",
        ['number'=>$hits]
      ));
    }

    // Retrieve record data
    Yii::debug("Getting row data from remote Z39.50 database ...", Module::CATEGORY);
    $yaz->setRange(1, $hits);
    $yaz->present();
    $error = $yaz->getError();
    if ($error) {
      Yii::debug("Server error (yaz_present): $error. Aborting.", Module::CATEGORY);
      throw new UserErrorException(Yii::t(Module::CATEGORY, "Server error: $error."));
    }

    // Retrieve as MARC XML
    $result = new MarcXmlResult($yaz);
    for ($i = 1; $i <= $hits; $i++) {
      try {
        $result->addRecord($i);
        if ($progressBar){
          $progressBar->setProgress(10 + (($i / $hits) * 80),
            Yii::t( Module::CATEGORY,"Retrieving {index} of {number} records...",   [ 'index' =>$i, 'number' => $hits])
          );
        }
      } catch (YazException $e) {
        if (stristr($e->getMessage(), "timeout")) {
          throw new UserErrorException(
            Yii::t( Module::CATEGORY,
              "Server timeout trying to retrieve {number} records: try a more narrow search",
              ['number'=>$hits]
            )
          );
        }
        throw new UserErrorException(
          Yii::t(Module::CATEGORY,"Server error: {error}.", ['error' => $e->getMessage()])
        );
      }
    }

    // visually separate verbose output for debugging
    function ml($description, $text)
    {
      $hl = "\n" . str_repeat("-", 100) . "\n";
      return $hl . $description . $hl . $text . $hl;
    }

    //Yii::debug(ml("XML", $result->getXml()), Module::CATEGORY);
    Yii::debug("Formatting data...", Module::CATEGORY);

    if ($progressBar) {
      $progressBar->setProgress(90, Yii::t(Module::CATEGORY, "Formatting records..."));
    }

    // convert to MODS
    $mods = $result->toMods();
    //Yii::debug(ml("MODS", $mods), Module::CATEGORY);

    // convert to bibtex and fix some issues
    $xml2bib = \app\modules\bibutils\Module::createCmd("xml2bib");
    $bibtex = $xml2bib->call("-nl -fc -o unicode", $mods);
    $bibtex = str_replace("\nand ", "; ", $bibtex);
    //Yii::debug(ml("BibTeX", $bibtex), Module::CATEGORY);

    // convert to array
    $parser = new BibtexParser();
    $records = $parser->parse($bibtex);

    if (count($records) === 0) {
      Yii::debug("Empty result set, aborting...", Module::CATEGORY);
      throw new UserErrorException(Yii::t(Module::CATEGORY, "Cannot convert server response"));
    }

    // saving to local cache
    Yii::debug("Caching records...", Module::CATEGORY);

    $step = 10 / count($records);
    $i = 0;


    foreach ($records as $item) {
      if ($progressBar) {
        $progressBar->setProgress(
          round (90 + ($step * $i++)),
          Yii::t(Module::CATEGORY, "Caching records...")
        );
      }

      $p = $item->getProperties();

      // fix bibtex parser issues and prevemt validation errors
      foreach ( $p as $key => $value ) {
        switch ($key){
          case "author":
          case "editor":
            $p[$key] = str_replace("{", "", $p[$key]);
            $p[$key] = str_replace("}", "", $p[$key]);
        }
        $columnSchema = Record::getDb()->getTableSchema(Record::tableName())->getColumn($key);
        if( $columnSchema === null ) {
          Yii::warning("Skipping non-existent column '$key'...");
          unset($p[$key]);
        } elseif( is_string($value) and $columnSchema->size ){
          $p[$key] = substr( $value, 0, $columnSchema->size );
        }
      }

      // create record
      $dbRecord = new Record($p);
      $dbRecord->setAttributes([
        'citekey' => $item->getItemID(),
        'reftype' => $item->getItemType(),
        'SearchId' => $searchId
      ]);
      $dbRecord->save();
      //Yii::debug(ml("Model Data", print_r($dbRecord->getAttributes(), true)), Module::CATEGORY);
    }
  }
}
