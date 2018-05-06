<?php

namespace app\modules\webservices\controllers;

use app\models\User;
use lib\cql\Diagnostic;
use lib\cql\Parser;
use lib\exceptions\TimeoutException;
use Yii;
use Exception;
use app\controllers\{ traits\AuthTrait, traits\DatasourceTrait };
use app\modules\webservices\Module;
use app\models\Datasource;
use app\modules\webservices\models\{ Record, Search, Datasource as WebservicesDatasource };
use lib\dialog\ServerProgress;
use lib\exceptions\UserErrorException;
use lib\bibtex\BibtexParser;

/**
 * Class ProgressController
 * @package modules\webservices\controllers
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
    $this->actionProgress("webservices_crossref","9780804767712","1234");
  }

  /**
   * Executes a request on the remote server. Called
   * by the ServerProgress widget on the client. If server times out
   * it will retry up to three times.
   *
   * @param string $datasource The name of the datasource
   * @param string $query The cql query
   * @param string $id The id of the progress widget
   * @return string Chunked HTTP response
   * @todo use DTO
   */
  public function actionProgress($datasource, $query, $id)
  {
    static $retries = 0;
    $progressBar = new ServerProgress($id);
    try {
      $this->sendRequest($datasource, $query, $progressBar);
      $progressBar->dispatchClientMessage("webservices.dataReady", $query);
      return $progressBar->complete();
    } catch (TimeoutException $e) {
      // retry
      if( $retries < 4){
        $progressBar->setProgress(0, Yii::t("webservices", "Server timed out. Trying again..."));
        sleep(rand(1,3));
        return $this->actionProgress($datasource, $query, $progressBar );
      } else {
        return $progressBar->error(Yii::t("webservices", "Server timed out."));
      }
    } catch (UserErrorException $e) {
      return $progressBar->error($e->getMessage());
    } catch (Exception $e) {
      Yii::error($e);
      return $progressBar->error($e->getMessage());
    }
  }


  /**
   * Does the actual work of executing the request on the remote server.
   *
   * @param string $datasourceName
   * @param $query
   * @param ServerProgress|null $progressBar
   *    A progressbar object responsible for displaying the progress
   *    on the client (optional)
   * @return void
   * @throws TimeoutException
   * @throws UserErrorException
   * @throws Exception
   */
  public function sendRequest( string $datasourceName, $query, ServerProgress $progressBar = null)
  {
    $datasource = Datasource::getInstanceFor($datasourceName);
    if( ! $datasource or ! $datasource instanceof WebservicesDatasource ){
      throw new \InvalidArgumentException("Invalid datasource '$datasourceName'.");
    }
    // set datasource table prefixes
    Search::setDatasource($datasource);
    Record::setDatasource($datasource);

    // remember last datasource used
    $this->module->setPreference("lastDatasource", $datasourceName );

    $connector = $datasource->createConnector($datasourceName);
    $query = $connector->fixQuery($query->query->cql);
    $cql = (new Parser($query))->query();
    if( $cql instanceof Diagnostic ){
      throw new UserErrorException(Yii::t( Module::CATEGORY, "Could not parse query: {error}", [
        'error' => $cql->toTxt()
        ]));
    }

    Yii::debug("Executing query '{$cql->toCQL()}' on webservice '$datasourceName' ...", Module::CATEGORY);

    if ($progressBar) {
      $progressBar->setProgress(0, Yii::t(Module::CATEGORY, "Waiting for webservice..."));
    }

    $records = $connector->query($cql);
    $hits = count($records);

    Yii::debug("$hits hits.", Module::CATEGORY);
    Yii::debug($records[0]->getAttributes());
    return;

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

    //Yii::debug(ml("XML", $result->getXml()), Module::CATEGORY);
    Yii::debug("Formatting data...", Module::CATEGORY);

    if ($progressBar) {
      $progressBar->setProgress(90, Yii::t(Module::CATEGORY, "Formatting records..."));
    }

    // convert to MODS
    $mods = $result->toMods();
    //Yii::debug(ml("MODS", $mods), Module::CATEGORY);

    // convert to bibtex and fix some issues
    $xml2bib = new Executable( "xml2bib", BIBUTILS_PATH );
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