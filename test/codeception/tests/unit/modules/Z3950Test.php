<?php

namespace tests\unit\models;

use app\modules\z3950\lib\yaz\{
  CclQuery, MarcXmlResult, Yaz, YazException
};
use app\modules\z3950\Module;
use Yii;
use lib\bibtex\BibtexParser;
use lib\util\Executable;

class Z3950Test extends \tests\unit\Base
{

  /**
   * @return string
   * @throws YazException
   * @throws \Exception
   */
  public function testYaz()
  {
    if (isset($_ENV["TRAVIS"]) or isset($_SERVER['TRAVIS'])) {
      $this->markTestSkipped("Doesn't work on Travis...");
    }
    /** @var Module $module */
    $module = Yii::$app->getModule("z3950");
    $gbvpath = $module->serverDataPath . "/z3950.loc.gov-7090-voyager.xml";
    $yaz = new Yaz( $gbvpath );
    $yaz->connect();
    $yaz->ccl_configure(array(
      "title"     => "1=4",
      "author"    => "1=1004",
      "keywords"  => "1=21",
      "year"      => "1=31"
    ) );
    codecept_debug( "Searching for Shakespeare's works...");
    $query = new CclQuery("author=shakespeare");
    $yaz->search( $query );
    $yaz->wait();
    $hits = $yaz->hits();
    codecept_debug( "$hits hits.");
    $maxNumber = 5;
    codecept_debug( "Retrieving $maxNumber records...");
    $yaz->setSyntax("USmarc");
    $yaz->setElementSet("F");
    $yaz->setRange( 1, $maxNumber );
    $yaz->present();
    codecept_debug( "Converting records to MODS...");
    $result = new MarcXmlResult($yaz);
    for( $i=1; $i <= $maxNumber; $i++) {
      $result->addRecord( $i );
    }
    $mods = $result->toMods();
    codecept_debug( "Converting records to BibTeX...");
    $xml2bib = new Executable("xml2bib");
    $bibtex = $xml2bib->call("-nl -b -o unicode", $mods );
    $parser = new BibtexParser;
    $records =  $parser->parse( $bibtex );
    codecept_debug("Retrieved " . count($records) . " records.");
    $this->assertCount($maxNumber, $records );
  }
}
