<?php

namespace app\tests\unit\base;

// for whatever reason, this is not loaded early enough
require_once __DIR__ . "/../../_bootstrap.php";

use app\models\Reference;
use lib\cql\NaturalLanguageQuery;
use lib\cql\Parser;
use yii\db\ActiveQuery;

class CqlTest extends \app\tests\unit\Base
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  // tests
  public function testCqlQuery()
  {
    $textquery = "author = Shakespeare and year > 1600";
    $parser = new Parser($textquery);
    $cqlquery = $parser->query();
    $this->tester->assertEquals('(author = "Shakespeare"  AND year > "1600" )', $cqlquery->toCQL());
    $txt = "lib\cql\Triple: \n    lib\cql\SearchClause: \n    lib\cql\Index: author\n    lib\cql\Relation: =\n    lib\cql\Term: Shakespeare\n  lib\cql\Boolean: and\n    lib\cql\SearchClause: \n    lib\cql\Index: year\n    lib\cql\Relation: >\n    lib\cql\Term: 1600\n";
    $this->tester->assertEquals($txt, $cqlquery->toTxt());
  }

  public function testBibliographQuery()
  {
    $fulltext_index ="`abstract`,`annote`,`author`,`booktitle`,`subtitle`,`contents`,`editor`,`howpublished`,`journal`,`keywords`,`note`,`publisher`,`school`,`title`,`year`";
    $match_mode = "NATURAL LANGUAGE MODE";
    $tests = [
      'title contains "Much Ado" and year is greater than 1598' =>
        "(`title` LIKE '%Much Ado%') AND (`year` > 1598)",
      'freeform search without operator in natural language mode' =>
        "MATCH($fulltext_index) AGAINST ('freeform search without operator in natural language mode' IN $match_mode )",
      '"lala land" and year=2018' =>
        "(MATCH($fulltext_index) AGAINST ('lala land' IN $match_mode )) AND (`year`='2018')"
    ];
    foreach ($tests as $query => $where) {
      $nlquery = new NaturalLanguageQuery([
        'query' => $query,
        'schema' => new \app\schema\BibtexSchema()
      ]);
      $activeQuery = new ActiveQuery(new Reference());
      $nlquery->injectIntoYiiQuery($activeQuery);
      $expected = "SELECT * FROM `data_Reference` WHERE " . $where;
      $this->tester->assertEquals($expected, $activeQuery->createCommand()->rawSql);
    }
  }

  /**
   * FIXME translation doesn't work
   */
  public function testMultiLanguageQuery()
  {
    $tests = [
      'en-US' => 'title contains "Much Ado" and year is greater than 1598',
      //'de-DE' => 'titel enthält "Much Ado" und jahr ist größer als 1598'
    ];
    foreach ($tests as $locale => $query) {
      $nlquery = new NaturalLanguageQuery([
        'query' => $query,
        'schema' => new \app\schema\BibtexSchema(),
        'language' => $locale,
        'verbose' => true
      ]);
      $activeQuery = new ActiveQuery(new Reference());
      $nlquery->injectIntoYiiQuery($activeQuery);
      $expected = "SELECT * FROM `data_Reference` WHERE (`title` LIKE '%Much Ado%') AND (`year` > 1598)";
      $this->tester->assertEquals($expected, $activeQuery->createCommand()->rawSql);
    }
  }
}