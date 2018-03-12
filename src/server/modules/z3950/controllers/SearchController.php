<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 09.03.18
 * Time: 08:26
 */

namespace modules\z3950\controllers;

use Yii;
use Exception;
use app\modules\z3950\Module;
use app\models\Datasource;
use app\modules\z3950\models\{
  Record, Result, Search, Datasource as Z3950Datasource
};
use app\modules\z3950\lib\yaz\{
  CclQuery, MarcXmlResult, Yaz, YazException
};
use lib\dialog\ServerProgress;
use lib\exceptions\UserErrorException;
use lib\bibtex\BibtexParser;
use lib\util\Executable;

/**
 * Class ProgressController
 * @package modules\z3950\controllers
 * @property Module $module
 */
class SearchController extends yii\web\Controller
{
  /**
   * Executes a Z39.50 request on the remote server. Called
   * by the ServerProgress widget on the client
   *
   * @param string $datasourceName
   * @param $query
   * @param string $progressWidgetId
   * @return string Chunked HTTP response
   * @todo use DTO
   */
  public function actionRequestProgress($datasourceName, $query, $progressWidgetId)
  {
    $progressBar = new ServerProgress($progressWidgetId);
    try {
      $this->sendRequest($datasourceName, $query, $progressBar);
      $progressBar->dispatchClientMessage("z3950.dataReady", $query);
      return $progressBar->complete();
    } catch (UserErrorException $e) {
      return $progressBar->error($e->getMessage());
    } catch (Exception $e) {
      Yii::warning($e->getFile() . ", line " . $e->getLine() . ": " . $e->getMessage());
      return $progressBar->error($e->getMessage());
    }
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
   * @throws UserErrorException
   * @throws Exception
   */
  public function sendRequest( string $datasourceName, $query, ServerProgress $progressBar = null)
  {
    $datasource = Datasource::findByNamedId($datasourceName);
    if( ! $datasource or ! $datasource instanceof Z3950Datasource ){
      throw new \InvalidArgumentException("Invalid datasource '$datasourceName'.");
    }
    // set datasource table prefixes
    Search::setDatasource($datasourceName);
    Result::setDatasource($datasourceName);
    Record::setDatasource($datasourceName);

    // remember last datasource used
    $this->module->setPreference("lastDatasource", $datasource->namedId);
    $query = $this->module->fixQueryString($query);

    Yii::debug("Executing query '$query' on remote Z39.50 database '$datasource' ...", Module::CATEGORY);

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

    // Result
    $yaz->wait();

    $error = $yaz->getError();
    if ($error) {
      Yii::debug("Server error (yaz_wait): $error. Aborting.", Module::CATEGORY);
      throw new UserErrorException(Yii::t(Module::CATEGORY, "Server error: %s", $error));
    }

    $info = [];
    $hits = $yaz->hits($info);
    Yii::debug("(Optional) result information: " . json_encode($info), Module::CATEGORY);

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

    Yii::debug("Created new search record for query '$query' for user #$userId.", Module::CATEGORY);

    if ($progressBar) {
      $progressBar->setProgress(10, Yii::t(
        Module::CATEGORY, "{number} records found.",
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

    Yii::debug(ml("XML", $result->getXml()), Module::CATEGORY);
    Yii::debug("Formatting data...", Module::CATEGORY);

    if ($progressBar) {
      $progressBar->setProgress(90, Yii::t(Module::CATEGORY, "Formatting records..."));
    }

    // convert to MODS
    $mods = $result->toMods();
    Yii::debug(ml("MODS", $mods), Module::CATEGORY);

    // convert to bibtex and fix some issues
    $xml2bib = new Executable( "xml2bib", BIBUTILS_PATH );
    $bibtex = $xml2bib->call("-nl -fc -o unicode", $mods);
    $bibtex = str_replace("\nand ", "; ", $bibtex);
    Yii::debug(ml("BibTeX", $bibtex), Module::CATEGORY);

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
    $searchId = $search->id;

    foreach ($records as $item) {
      if ($progressBar) {
        $progressBar->setProgress(
          round (90 + ($step * $i++)),
          Yii::t(Module::CATEGORY, "Caching records...")
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
        'SearchId' => $searchId
      ]);
      $dbRecord->save();
      //Yii::debug(ml("Model Data", print_r($dbRecord->getAttributes(), true)), Module::CATEGORY);
    }
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