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

use \Yii;
use Parser;
use \lib\schema\Tokenizer;

/**
 * Tool for working with the CQL query language
 *
 * @see http://www.loc.gov/standards/sru/resources/cql-context-set-v1-2.html
 */
class Query extends \yii\base\BaseObject
{

  /**
   * Booleans
   * @var array
   */
  public $booleans = array( "and" /*, "or", "not"*/ ); // currently, only "and" is supported

  /**
   * Modifiers. Make sure that longer expressions that contain other expressions ("isnot" - "is")
   * appear first, otherwise the shorter ones will be substituted first, making the longer ones
   * unparsable.
   * @var array
   */
  public $modifiers = array(
    "isnot", "is", "notcontains", "contains", "startswith",
    "=", ">", ">=", "<", "<=", "<>"
  );

  protected $dictionary = array();

  /**
   * Exists only for POEditor to pick up the translation messages.
   * @todo Yii'ify
   */
  function marktranslations()
  {
    _("and"); _("or"); _("not");
    _("is"); _("isnot"); _("contains"); _("notcontains");
    _("startswith");
  }

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct();
    
    // save dictionary in the session
    // @todo this is a hack to work around the fact that translation doesn't
    // work the way it should
    $this->dictionary =& $_SESSION['bibliograph_schema_CQL#dictionary'];
  }

  /**
   * Returns the dictionary of words to be translated into english
   * booleans, modifiers or object properties
   * @param \app\models\Reference $model
   * @return array The dictionary for the model
   */
  protected function getDictionary( \app\models\Reference $model)
  {
    not_implemented(); 
    $modelClass = get_class($model);
    if( ! $this->dictionary[ $modelClass ] )
    {
      $localeMgr = qcl_locale_Manager::getInstance();
      
      $availableLocales = $localeMgr->getAvailableLocales();
      $dict = array();

      // translate words for each locale
      foreach( $availableLocales as $locale)
      {
        
        $localeMgr->setLocale($locale);
        $textdomain = "bibliograph_$locale";
        bindtextdomain($textdomain, "./locale");

        // model indexes
        foreach ( $model::getSchema()->getIndexNames() as $index )
        {
          $fields = $model::getSchema()->getIndexFields( $index );
          // @todo we only use the first, but it should really search all of them
          $property = $fields[0];
         
          $translated = mb_strtolower( dgettext($textdomain,$index), 'UTF-8');
   
          $dict[$translated]=$property;
          // add the root form of German gendered words ("Autor/in"=> "Autor")
          if( $pos = strpos( $translated, "/" ) )
          {
            $dict[substr($translated,0,$pos)] = $property;
          }
        }

        // modifiers and booleans
        foreach( array_merge($this->modifiers, $this->booleans) as $word)
        {
          // skip non-words
          if( strtolower($word) == strtoupper($word) ) continue;
          $translated = mb_strtolower( $localeMgr->tr($word), 'UTF-8' );
          $dict[$translated]=$word;
        }

      }
      // revert to standard locale
      $localeMgr->setLocale();

      $this->dictionary[ $modelClass ] = $dict;
    }
    return $this->dictionary[ $modelClass ];
  }


  /**
   * Adds conditions to a DB query object from a qcl query
   *
   * @param stdClass $query
   *    The query data object from the json-rpc request
   * @param qcl_data_db_Query $qclQuery
   *    The query object used by the query behavior
   * @param bibliograph_model_ReferenceModel $model
   *    The model on which the query should be performed
   * @throws bibliograph_schema_Exception
   * @throws Exception
   * @throws JsonRpcException
   * @return qcl_data_db_Query
   */
  public function addQueryConditions(
    stdClass $query,
    qcl_data_db_Query $qclQuery,
    bibliograph_model_ReferenceModel $model
  ){
    not_implemented(); 
    // get qcl query
    $error = "First argument must be object and have a 'cql' property";
    qcl_assert_has_property( $query, "cql", $error );
    qcl_assert_valid_string( $query->cql, $error );
    $cqlQuery = trim($query->cql);

    // Translate operators, booleans and indexes.
    $tokenizer    = new lib\schema\Tokenizer($cqlQuery);
    $tokens       = $tokenizer->tokenize();
    $dict         = $this->getDictionary($model);
    $operators    = array_merge($this->booleans,$this->modifiers);
    $hasOperator  = false;
    $translTokens = array();

    do {
      $token = mb_strtolower( array_shift( $tokens ), "UTF-8" );

      // do not translate quoted expressions
      if ( $token[0] == '"' )
      {
        $translTokens[] = $token;
        continue;
      }

      // translate from dictionary

      // simple case: direct match
      if ( isset($dict[$token]) )
      {
        $token = $dict[$token];
      }

      // try the combination with next token
      elseif( count($tokens) > 0 )
      {
        $extToken = $token . " " . $tokens[0];
        if ( isset($dict[$extToken]) )
        {
          $token = $dict[$extToken];
          array_shift( $tokens );
        }
      }

      // check if token is an operator
      if( in_array( $token, $operators ) )
      {
        $hasOperator = true;
      }

      $translTokens[] = $token;
    }
    while( count( $tokens ) );

    $this->log( "Translated tokens: " . implode(" ", $translTokens ) );

    // Re-assemble translated query string
    if ( $hasOperator )
    {
      $cqlQuery = implode( " ", $translTokens );
    }

    // Queries that don't contain any operators or booleans are converted into a
    // query connected by "AND"
    else
    {
      $cqlQuery = implode( " and ", $translTokens );
    }

    // create and configure parser object
    $parser = new Parser( $cqlQuery );
    $parser->setBooleans( $this->booleans );
    $parser->setModifiers( $this->modifiers );
    $parser->setSortWords( array("sortby" ) );

    /*
     * parse CQL string
     */
    $cqlObject = $parser->query();
    if ( $cqlObject instanceof cql_Diagnostic )
    {
      throw new \Exception( "Could not parse query." );
    }

    /*
     * populate query object
     */
    $this->convertCqlObjectToQclQuery( $cqlObject, $qclQuery, $model );

    $this->log( "'Where' structure: " . json_encode($qclQuery->where) );

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
   * @throws JsonRpcException
   * @throws bibliograph_schema_Exception
   * @return void
   * @todo implement other operators, this requires reworking of how
   * the 'where' queries are created in the QueryBehavior
   */
  protected function convertCqlObjectToQclQuery(
    cql_Object $cqlObject,
    qcl_data_db_Query $qclQuery,
    bibliograph_model_ReferenceModel $model
  ){
    if ( $cqlObject instanceof cql_Triple )
    {
      $this->convertCqlObjectToQclQuery( $cqlObject->leftOperand, $qclQuery, $model );
      $this->convertCqlObjectToQclQuery( $cqlObject->rightOperand, $qclQuery, $model );
    }
    elseif ( $cqlObject instanceof cql_SearchClause )
    {
      /*
       * look for index. for now, if there is no index,
       * use an index named 'fulltext', which must exist in the model.
       */
      $index = $cqlObject->index->value;
      if( ! $index )
      {
        $index = "fulltext";
        $property = null;
      }

      /*
       * else, translate index into property
       */
      else
      {
        if( $model->hasProperty( $index ) )
        {
          $property = $index;
        }
        else
        {
          throw new bibliograph_schema_Exception(Yii::t('app',"Index '%s' does not exist.", $index ) );
        }
        $index = null;
      }

      $relation = strtolower($cqlObject->relation->value);
      $term     = str_replace('"',"", $cqlObject->term->value);

      switch( $relation )
      {
        /*
         * simple field comparison. compare numeric values normally
         * and replace "*" with "%" for "LIKE" comparisons for strings
         */
        case "=":
        case "is":
          if( is_numeric($term) )
          {
            $operator = "=";
          }
          else
          {
            $operator = "LIKE";
            $term = str_replace("*","%",$term);
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
          throw new JsonRpcException("Cannot yet deal with relation '$relation'. " . typeof( $cqlObject ) );
      }

      if ( $property )
      {
        // @todo OR and NOT connectors
        $qclQuery->where[$property] = array( $operator, $term );
      }
      elseif ( $index )
      {
        $qclQuery->match[$index] = trim( $qclQuery->match[$index] . " " . $term );
      }

    }

    /**
     * Syntax error
     */
    elseif ( $cqlObject instanceof cql_Diagnostic )
    {
      throw new JsonRpcException( $cqlObject->toTxt() );
    }

    /**
     * Unknown Object, shouldn't ever get here
     */
    else
    {
      throw new LogicException("Cannot yet deal with object " . get_class( $cqlObject ) );
    }
  }
}
