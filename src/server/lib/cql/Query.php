<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

namespace lib\schema\cql;

use app\models\Datasource;
use lib\exceptions\UserErrorException;
use \Yii;
use \lib\schema\cql\Parser;


/**
 * Tool for working with the CQL query language
 *
 * @see http://www.loc.gov/standards/sru/resources/cql-context-set-v1-2.html
 * @property array modifiers
 */
class Query extends \yii\base\BaseObject
{

  /**
   * Booleans
   * @var array
   */
  public $booleans = array("and" /*, "or", "not"*/); // currently, only "and" is supported

  /**
   * Modifiers. Make sure that longer expressions that contain other expressions ("isnot" - "is")
   * appear first, otherwise the shorter ones will be substituted first, making the longer ones
   * unparsable.
   * @var array
   */
  public function getModifiers()
  {
    return array_keys( $this->getModifierTranslations() );
  }

  protected $dictionary = array();

  /**
   * Exists only for POEditor to pick up the translation messages.
   * @todo Yii'ify
   */
  public function getModifierTranslations()
  {
    return [
      "and"   => Yii::t('app', "{operand1} and {operand2}"),
      "or"    => Yii::t('app', "{operand1} or {operand2}"),
      "!="   => Yii::t('app', "{operand1} is not {operand2}"),
      "="    => Yii::t('app', "{operand1} is {operand2}"),
      "contains"    => Yii::t('app', "{operand1} contains {operand2}"),
      "notcontains" => Yii::t('app', "{operand1} does not contain {operand2}"),
      "startswith"  => Yii::t('app', "{operand1} starts with {operand2}"),
      ">"     => Yii::t('app', "{operand1} is greater than {operand2}"),
      ">="    => Yii::t('app', "{operand1} is greater than or equal {operand2}"),
      "<"     => Yii::t('app', "{operand1} is smaller than {operand2}"),
      "<="    => Yii::t('app', "{operand1} is smaller than or equal {operand2}"),
    ];
  }

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Returns the dictionary of words to be translated into english
   * booleans, modifiers or object properties
   * @param \app\schema\AbstractReferenceSchema $schema
   * @return array The dictionary for the model
   * @todo cache data
   */
  function getDictionary(\app\schema\AbstractReferenceSchema $schema)
  {
    $schemaClass = get_class($schema);
    if (!$this->dictionary[$schemaClass]) {
      $lang = Yii::$app->language;
      // model indexes
      foreach ($schema->getIndexNames() as $index) {
        $fields = $schema->getIndexFields($index);
        foreach ($fields as $field ) {
          $translated = \mb_strtolower(Yii::t('app', $field), 'UTF-8');
          $dict[$translated] = $field;
          // add the root form of German gendered words ("Autor/in"=> "Autor")
          if ($pos = strpos($translated, "/")) {
            $dict[substr($translated, 0, $pos)] = $field;
          }
        }
      }
      // modifiers and booleans
      foreach (array_merge($this->modifiers, $this->booleans) as $word) {
        // skip non-words
        if (\mb_strtolower($word, 'UTF-8') == \mb_strtoupper($word, 'UTF-8')) continue;
        $translated = \mb_strtolower( Yii::t('app', $word), 'UTF-8');
        $dict[$translated] = $word;
      }
      $this->dictionary[$schemaClass] = $dict;
    }
    return $this->dictionary[$schemaClass];
  }


  /**
   * Modifies the query with conditions supplied by the client
   *
   * The client query looks like this:
   * {
   *   'datasource' : "<datasource name>",
   *   'modelType' : "reference",
   *   'query' : {
   *     'properties' : ["author","title","year" ...],
   *     'orderBy' : "author",
    *    'cql' : 'author contains shakespeare'
   *   }
   * }
   * @param \stdClass $clientQueryData
   *    The query data object from the json-rpc request
   * @param \yii\db\ActiveQuery $serverQuery
   *    The ActiveQuery object used
   * @return \yii\db\ActiveQuery
   * @throws \InvalidArgumentException
   */
  public function addQueryConditions( \stdClass $clientQueryData, \yii\db\ActiveQuery $serverQuery)
  {
    // get qcl query

    $cqlQuery = trim($clientQueryData->cql);
    $datasource = Datasource::getInstanceFor($clientQueryData->datasource);
    /** @var \app\schema\BibtexSchema $schema */
    $schema = $datasource->getSchema()->one();

    // Translate operators, booleans and indexes.
    $tokenizer = new \lib\util\Tokenizer($cqlQuery);
    $tokens = $tokenizer->tokenize();
    $dict = $this->getDictionary($schema);
    $operators = array_merge($this->booleans, $this->modifiers);
    $hasOperator = false;
    $translTokens = array();

    do {
      $token = mb_strtolower(array_shift($tokens), "UTF-8");

      // do not translate quoted expressions
      if ($token[0] == '"') {
        $translTokens[] = $token;
        continue;
      }

      // translate from dictionary

      // simple case: direct match
      if (isset($dict[$token])) {
        $token = $dict[$token];
      } // try the combination with next token
      elseif (count($tokens) > 0) {
        $extToken = $token . " " . $tokens[0];
        if (isset($dict[$extToken])) {
          $token = $dict[$extToken];
          array_shift($tokens);
        }
      }

      // check if token is an operator
      if (in_array($token, $operators)) {
        $hasOperator = true;
      }

      $translTokens[] = $token;
    } while (count($tokens));

    Yii::debug("Translated tokens: " . implode(" ", $translTokens));

    // Re-assemble translated query string
    if ($hasOperator) {
      $cqlQuery = implode(" ", $translTokens);
    }

    // Queries that don't contain any operators or booleans are converted into a
    // query connected by "AND"
    else {
      $cqlQuery = implode(" and ", $translTokens);
    }

    // create and configure parser object
    $parser = new Parser($cqlQuery);
    $parser->setBooleans($this->booleans);
    $parser->setModifiers($this->modifiers);
    $parser->setSortWords(array("sortby"));

    // parse CQL string
    $cqlObject = $parser->query();
    if ($cqlObject instanceof cql_Diagnostic) {
      throw new UserErrorException(Yii::t('app',"Could not parse query."));
    }

    // populate query object
    $this->convertCqlObjectToQclQuery($cqlObject, $qclQuery, $model);

    Yii::trace("'Where' structure: " . json_encode($qclQuery->where));

    return $qclQuery;
  }


  /**
   * Recursive function to convert a CQL object structure into
   * a qcl_data_db_Query. Boolean operators are ignored at the moment,
   * everything is connected with boolean "AND".
   *
   * @param cql_Object $cqlObject
   * @param qcl_data_db_Query $qclQuery
   * @param bibliograph_model_ReferenceModel $model
   * @throws LogicException
   * @throws \lib\exceptions\UserErrorException
   * @throws bibliograph_schema_Exception
   * @return void
   * @todo implement other operators, this requires reworking of how
   * the 'where' queries are created in the QueryBehavior
   */
  protected function convertCqlObjectToQclQuery(
    cql_Object $cqlObject,
    qcl_data_db_Query $qclQuery,
    bibliograph_model_ReferenceModel $model
  )
  {
    if ($cqlObject instanceof cql_Triple) {
      $this->convertCqlObjectToQclQuery($cqlObject->leftOperand, $qclQuery, $model);
      $this->convertCqlObjectToQclQuery($cqlObject->rightOperand, $qclQuery, $model);
    } elseif ($cqlObject instanceof cql_SearchClause) {
      /*
       * look for index. for now, if there is no index,
       * use an index named 'fulltext', which must exist in the model.
       */
      $index = $cqlObject->index->value;
      if (!$index) {
        $index = "fulltext";
        $property = null;
      } /*
       * else, translate index into property
       */
      else {
        if ($model->hasProperty($index)) {
          $property = $index;
        } else {
          throw new bibliograph_schema_Exception(Yii::t('app', "Index '%s' does not exist.", $index));
        }
        $index = null;
      }

      $relation = strtolower($cqlObject->relation->value);
      $term = str_replace('"', "", $cqlObject->term->value);

      switch ($relation) {
        /*
         * simple field comparison. compare numeric values normally
         * and replace "*" with "%" for "LIKE" comparisons for strings
         */
        case "=":
        case "is":
          if (is_numeric($term)) {
            $operator = "=";
          } else {
            $operator = "LIKE";
            $term = str_replace("*", "%", $term);
          }
          break;

        /*
         * containing values
         */
        case "contains":
          $operator = "LIKE";
          $term = "%$term%";
          break;

        case "notcontains":
          $operator = "NOT LIKE";
          $term = "%$term%";
          break;

        case "startswith":
          $operator = "LIKE";
          $term = "$term%";
          break;

        case ">":
        case "<":
        case ">=":
        case "<=":
          $operator = $relation;
          break;

        case "<>":
        case "isnot":
          $operator = "!=";
          break;

        default:
          throw new \lib\exceptions\UserErrorException("Cannot yet deal with relation '$relation'. " . typeof($cqlObject));
      }

      if ($property) {
        // @todo OR and NOT connectors
        $qclQuery->where[$property] = array($operator, $term);
      } elseif ($index) {
        $qclQuery->match[$index] = trim($qclQuery->match[$index] . " " . $term);
      }

    } /**
     * Syntax error
     */
    elseif ($cqlObject instanceof cql_Diagnostic) {
      throw new \lib\exceptions\UserErrorException($cqlObject->toTxt());
    } /**
     * Unknown Object, shouldn't ever get here
     */
    else {
      throw new LogicException("Cannot yet deal with object " . get_class($cqlObject));
    }
  }
}
