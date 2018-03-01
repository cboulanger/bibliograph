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
use PHPUnit\Framework\MockObject\RuntimeException;
use stdClass;
use \Yii;
use \lib\schema\cql\Parser;
use yii\db\ActiveQuery;


/**
 * Tool for working with the CQL query language
 *
 * @see http://www.loc.gov/standards/sru/resources/cql-context-set-v1-2.html
 * @property array $modifiers
 * @property array $booleans
 */
class Query extends \yii\base\BaseObject
{

  /**
   * @var \app\schema\AbstractReferenceSchema
   */
  protected $schema = null;

  /**
   * The dictionary of operators and modifiers
   * @var array
   */
  protected $dictionary = [];


  const TYPE_COMPARATOR = "comparator";

  const TYPE_BOOLEAN = "boolean";

  /**
   * Modifiers. Make sure that longer expressions that contain other expressions ("isnot" - "is")
   * appear first, otherwise the shorter ones will be substituted first, making the longer ones
   * unparsable.
   * @var array
   */
  public function getOperators()
  {
    return array_keys( $this->getOperatorData() );
  }

  /**
   * Returns the metadata on available operators. If an operator is passed as parameter, only
   * the metadata (type, translation) for this operator is returned. Otherwise, return
   * the data on all operators
   * @param null $operator
   * @return array
   */
  public function getOperatorData($operator = null)
  {
    static $data = null;
    if(! $data ) $data =[
      "and"   => [
        'translation' => Yii::t('app', "{leftOperand} and {rightOperand}"),
        'type' => self::TYPE_BOOLEAN
      ],
      "or"    => [
        'translation' => Yii::t('app', "{leftOperand} or {rightOperand}"),
        'type' => self::TYPE_BOOLEAN
      ],
      "!="   => [
        'translation' => Yii::t('app', "{field} is not {value}"),
        'type' => self::TYPE_COMPARATOR
      ],
      "="    => [
        'translation' => Yii::t('app', "{field} is {value}"),
        'type' => self::TYPE_COMPARATOR
      ],
      "contains" => [
        'translation' => Yii::t('app', "{field} contains {value}"),
        'type' => self::TYPE_COMPARATOR
      ],
      "notcontains" => [
        'translation' => Yii::t('app', "{field} does not contain {value}"),
        'type' => self::TYPE_COMPARATOR
      ],
      "startswith"  => [
        'translation' => Yii::t('app', "{field} starts with {value}"),
        'type' => self::TYPE_COMPARATOR
      ],
      ">" => [
        'translation' => Yii::t('app', "{field} is greater than {value}"),
        'type' => self::TYPE_COMPARATOR
      ],
      ">=" => [
        'translation' => Yii::t('app', "{field} is greater than or equal to {value}"),
        'type' => self::TYPE_COMPARATOR
      ],
      "<"  => [
        'translation' => Yii::t('app', "{field} is smaller than {value}"),
        'type' => self::TYPE_COMPARATOR
      ],
      "<=" => [
        'translation' => Yii::t('app', "{field} is smaller than or equal to {value}"),
        'type' => self::TYPE_COMPARATOR
      ],
      "sortby" => [
        'translation' => Yii::t('app', "sort by {value}"),
        'type' => self::TYPE_COMPARATOR
      ]
    ];
    return $operator ? $data[$operator] : $data;
  }

  /**
   * Getter method for modifiers property
   * @return array
   */
  protected function getModifiers(){
    return array_keys( array_filter( $this->getOperatorData(),
      function($item){
        return $item['type'] === self::TYPE_COMPARATOR;
      }));
  }

  /**
   * Getter method for booleans property
   * @return array
   */
  protected function getBooleans(){
    return array_keys( array_filter( $this->getOperatorData(),
      function($item){
        return $item['type'] === self::TYPE_BOOLEAN;
      }));
  }


  /**
   * Returns the dictionary of words to be translated into english
   * booleans, modifiers or object properties
   * @param \app\schema\AbstractReferenceSchema $schema
   * @return array The dictionary for the model
   * @todo cache data
   */
  protected function getDictionary()
  {
    $schemaClass = get_class($this->schema);
    if (!$this->dictionary[$schemaClass]) {
      $lang = Yii::$app->language;
      // model indexes
      foreach ($this->schema->getIndexNames() as $index) {
        $fields = $this->schema->getIndexFields($index);
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
      foreach ( $this->operators as $operator) {
        // skip non-words
        if (\mb_strtolower($operator, 'UTF-8') == \mb_strtoupper($operator, 'UTF-8')) continue;
        // save the lowercase version of the translation for fast lookup
        $translated = \mb_strtolower( $this->getOperatorData($operator)['translation'], 'UTF-8');
        $translated = trim(str_replace(['{operand1}','{operand2}'],'', $translated));
        $dict[$translated] = $operator;
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
   * @param stdClass $clientQueryData
   *    The query data object from the json-rpc request
   * @param \yii\db\ActiveQuery $serverQuery
   *    The ActiveQuery object used
   * @return \yii\db\ActiveQuery
   * @throws \InvalidArgumentException
   */
  public function addQueryConditions( stdClass $clientQueryData, ActiveQuery $serverQuery)
  {
    $cqlQuery = trim($clientQueryData->cql);
    $datasource = Datasource::getInstanceFor($clientQueryData->datasource);
    $this->schema = $datasource->getSchema()->one();
    $dict = $this->getDictionary();

    // Translate operators, booleans and indexes.
    $tokenizer = new \lib\util\Tokenizer($cqlQuery);
    $tokens = $tokenizer->tokenize();
    $operators = $this->getOperators();
    $hasOperator = false;
    $parsedTokens = [];

    do {
      $token = $tokens[0];

      // do not translate quoted expressions
      if ( in_array( $token[0], ["'", '"']) ) {
        $translatedTokens[] = $token;
        continue;
      }

      // translate from dictionary
      $foundTranslation = false;
      for($i=0; $i<count($tokens); $i++){
        $token = implode( " ", array_slice( $tokens, 0, $i ));
        $compare = mb_strtolower( $token, "UTF-8");
        if( isset( $dict[$compare] ) ){
          $token = $dict[$compare];
          $hasOperator = in_array( $token, $operators );
          break;
        }
      }
      $tokens = array_slice( $tokens, $i);
      $parsedTokens[] = $token;
    } while (count($tokens));

    Yii::debug("Translated tokens: " . implode(" ", $parsedTokens));

    // Re-assemble translated query string
    if ($hasOperator) {
      $cqlQuery = implode(" ", $translatedTokens);
    }

    // Queries that don't contain any operators or booleans are converted into a
    // query connected by "AND"
    else {
      $cqlQuery = implode(" and ", $translatedTokens);
    }

    // create and configure parser object
    $parser = new Parser($cqlQuery);
    $parser->setBooleans($this->booleans);
    $parser->setModifiers($this->modifiers);
    $parser->setSortWords(array("sortby"));

    // parse CQL string
    $cqlObject = $parser->query();
    if ($cqlObject instanceof Diagnostic) {
      throw new UserErrorException(Yii::t('app',"Could not parse query."));
    }

    // modify query object
    $this->addCqlData($cqlObject, $serverQuery);

    return $serverQuery;
  }


  /**
   * Recursive function to modif a CQL object structure into
   * an ActiveQuer. Boolean operators are ignored at the moment,
   * everything is connected with boolean "AND".
   */
  protected function addCqlData( Object $cqlObject, ActiveQuery $serverQuery)
  {
    if ($cqlObject instanceof Triple) {
      $this->addCqlData($cqlObject->leftOperand, $serverQuery);
      $this->addCqlData($cqlObject->rightOperand, $serverQuery);
    } elseif ($cqlObject instanceof SearchClause) {
      // look for index. for now, if there is no index,
      // use an index named 'fulltext', which must exist in the model.
      $index = $cqlObject->index->value;
      if (!$index) {
        $index = "fulltext";
        $property = null;
      } // else, translate index into property
      else {
        if (in_array($index, $this->schema->fields())) {
          $property = $index;
        } else {
          throw new UserErrorException(Yii::t('app', "Index '{1}' does not exist.", $index));
        }
        $index = null;
      }
      $relation = mb_strtolower($cqlObject->relation->value);
      $term = str_replace('"', "", $cqlObject->term->value);

      switch ($relation) {
        // simple field comparison. compare numeric values normally
        // and replace "*" with "%" for "LIKE" comparisons for strings
        case "=":
        case "is":
          if (is_numeric($term)) {
            $operator = "=";
          } else {
            $operator = "LIKE";
            $term = str_replace("*", "%", $term);
          }
          break;

        // containing values
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
          throw new UserErrorException("Cannot yet deal with relation '$relation'. " . typeof($cqlObject));
      }

      if ($property) {
        // @todo OR and NOT connectors
        //$qclQuery->where[$property] = array($operator, $term);
      } elseif ($index) {
        //$qclQuery->match[$index] = trim($qclQuery->match[$index] . " " . $term);
      }

    } // Syntax error
    elseif ($cqlObject instanceof Diagnostic) {
      throw new \lib\exceptions\UserErrorException($cqlObject->toTxt());
    } /**
     * Unknown Object, shouldn't ever get here
     */
    else {
      throw new RuntimeException("Cannot yet deal with object " . get_class($cqlObject));
    }
  }
}