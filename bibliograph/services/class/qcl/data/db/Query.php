<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2010 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 *  * Oliver Friedrich (jesus77)
 */

/**
 * An object holding data for use in a SQL query by a QueryBehavior class.
 *
 * @todo Rewrite using the planned qcl_data_query_* architecture. This
 * class should inherit from an abstract class or implement an interface
 * so that it can be replaced by a more sophisticated query object.
 * The goal is to parse string-type queries into an intermediate object
 * tree structure that then can be converted into the desired target format
 * this might include an sql -> object tree -> sql conversion for the sake
 * of data sanitation (in order to avoid injection), but more importantly,
 * it will make it possible to convert seamlessly from client-side queries
 * into server-side queries which can be safely executed across backends,
 * thus allowing to treat SQL-, XML-, or Hashtable-based etc. backends uniformly.
 */
class qcl_data_db_Query
  extends qcl_core_Object
{

  //-------------------------------------------------------------
  // Properties
  //-------------------------------------------------------------

  /**
   * Data for the SELECT statement. If "*" or null (default),
   * select all columns. if array, select the columns specified in
   * the array. If the array elements are associative arrays, evaluate
   * their keys as follows:  'column' => name of the column, 'as' => name
   * of the key in which the value is returned in the result row, this
   * allows to use tables that differ from the property schema.
   * 'function' => function object
   * @see qcl_data_db_function_ISqlFunction
   * @var string|array|null.
   */
  public $select = null;

  /**
   * Array of properties to retrieve. If "*" or null (default), retrieve all.
   * When using joined tables, the parameter must be an array containing two
   * arrays, the first with the properties of the model table, and the second
   * with the properties of the joined table. Alternatively, you can use the
   * syntax "prop1,prop2,prop3" for an unlinked, and
   * "prop1,prop2,prop3|prop1,prop2,prop3" for a linked model. It is also
   * possible to use "*" or "*|*" to get all properties from unlinked and
   * linked models, respectively.
   * @var string|array|null
   */
  public $properties = null;

  /**
   * The name of the affected table. Usually not needed since the
   * behavior takes care of this.
   * @var string
   */
  public $table = null;

  /**
   * 'Where' condition to match. If null, get all. if array or object, match all
   * key -> value combinations. If string, the table name is available as
   * the alias "t1", joined tables as "t2".
   * @var object|string|array|null
   */
  public $where = null;

  /**
   * A condition for index matches
   * @var array
   */
  public $match = null;

  /**
   * Order by property/properties.
   * @var string|array|null
   */
  public $orderBy  =null;

  /**
   * Group by property/properties.
   * @var string|array|null
   */
  public $groupBy  =null;

  /**
   * The first row to retrieve
   * @var int|null
   */
  public $firstRow = null;

  /**
   * The last row to retrieve.
   * @deprecated use $numberOfRows instead
   * @var int|null
   */
  public $lastRow = null;


  /**
   * The number of rows to retrieve.
   * @var int|null
   */
  public $numberOfRows = null;


  /**
   * Optional flag to select distict rows only
   * @var boolean
   */
  public $distinct = false;

  /**
   * Optional link condition. If provided, this will
   * cause the query behavior to automatically generate the necessary
   * join query.
   * @var array|null
   */
  public $link = null;

  /**
   * The parameters used to execute dynamic sql statements by controlled
   * replacement of placeholders
   * @see PDO::prepare()
   * @var array
   */
  public $parameters = array();

  /**
   * The types of the parameters used to execute dynamic sql statements
   * @see PDO::prepare()
   * @var array
   */
  public $parameter_types = array();

  /**
   * The reference to the PDOStatement object that is returned
   * by a PDO::prepare() call.
   * @var PDOStatement
   */
  public $pdoStatement;


  /**
   * The number of rows affected or retrieved by the last
   * query
   */
  public $rowCount;


  /**
   * Maps property names to keys that should replace the
   * property names in the result data
   * @var array
   */
  public $as = array();

  /**
   * Valid operators for where queries
   */
  public $operators= array(
    "like","is","is not","=",">","<",">=","<=","!=","in","not in", "not like", "between"
  );

  //-------------------------------------------------------------
  // Constructor
  //-------------------------------------------------------------

  /**
   * Constructor
   * @param array|null $map
   *   Optional map of properties to be set.
   */
  function __construct( $map = null )
  {
    if ( $map !== null )
    {
      $this->set( $map );
    }
  }

  //-------------------------------------------------------------
  // Getters
  //-------------------------------------------------------------

  public function getProperties()
  {
    return $this->properties;
  }

  public function getTable()
  {
    return $this->table;
  }

  public function getSelect() {
    return $this->select;
  }

  public function getWhere()
  {
    return $this->where;
  }

  public function getMatch()
  {
    return $this->match;
  }

  public function getParameters()
  {
    return $this->parameters;
  }

  public function getParameterTypes()
  {
    return $this->parameter_types;
  }

  public function getPdoStatement()
  {
    return $this->pdoStatement;
  }

  public function getRowCount()
  {
    return $this->rowCount;
  }

  /**
   * @return array
   */
  public function getLink()
  {
    return $this->link;
  }

  /**
   * Returns the legal operators for "where" queries
   * @return array
   */
  public function operators()
  {
    return $this->operators;
  }

  //-------------------------------------------------------------
  // converters
  //-------------------------------------------------------------


  /**
   * Checks if the operator used in the where query is valid and throws
   * an InvalidArgumentException if not.
   * @param string $operator
   * @return bool
   * @throws InvalidArgumentException
   */
  protected function checkOperator( $operator )
  {
    if ( in_array( strtolower( $operator ), $this->operators() ) )
    {
      return true;
    }
    throw new InvalidArgumentException("Operator '$operator' is invalid.");
  }

  /**
   * Converts the object to an sql statement. If necessary,
   * the 'parameter' and 'parameter_types' members will be modified.
   *
   * @param qcl_data_model_AbstractActiveRecord $model
   * @throws LogicException
   * @throws InvalidArgumentException
   * @return string sql statement
   * @todo rewrite this from scratch. We need a query parser that
   * translates string queries into a query-language-neutral intermediary
   * format and then allows to recompile the query in the output format,
   * thus allowing a) to separate instructions from data and preventing
   * injections and b) to use the same queries for a variety of backends.
   */
  public function toSql( qcl_data_model_AbstractActiveRecord $model)
  {
    $queryBehavior = $model->getQueryBehavior();
    $adpt    = $queryBehavior->getAdapter();
    $propArg = $this->getProperties();
    $selectArgs = $this->getSelect();

    /*
     * check for relations
     */
    $link    = $this->getLink();
    if ( $link and isset( $link['relation'] ) )
    {
      $relBeh = $model->getRelationBehavior();
      $relation = $link['relation'];
      $relBeh->checkRelation( $relation );
      $targetModel = $relBeh->getTargetModel( $relation );
    }
    else
    {
      $targetModel = null;
      $relation = null;
      $relBeh = null;
    }

    /*
     * determine the column to select and the names under
     * which the columns should be returned ('properties')
     * properties is an array, one element for each model
     * involved, of arrays of property names.
     */
    $columns    = array();
    $properties = array();

    /*
     * if string, split at the pipe and comma characters
     */
    if ( is_string( $propArg ) )
    {
      $parts = explode("|", $propArg );

      /*
       * if we have "p1,p2,p3|p4,p5,p6"
       */
      if ( count( $parts ) > 1 )
      {
        for ( $i=0; $i<count( $parts ); $i++  )
        {
          $properties[$i] = explode(",",$parts[$i] );
        }
      }

      /*
       * no only "p1,p2,p3"
       */
      else
      {
        $properties[0] = explode(",",$parts[0]);
      }
    }

    /*
     * We have an array.
     * If first element is a string, only the properties
     * of the current model are requested. Convert
     * the properties array accordingly
     */
    elseif ( is_array( $propArg ) )
    {
      if ( is_array( $propArg[0] ) )
      {
        $properties = $propArg;
      }
      elseif ( is_string( $propArg[0] ) )
      {
        $properties = array( $propArg );
      }
      else
      {
        throw new InvalidArgumentException("Invalid property argument");
      }
    }

    /*
     * if null, all the properties of the current model
     */
    elseif ( is_null( $propArg ) )
    {
      $properties = array( "*" );
    }

    /*
     * invalid property arguments
     */
    else
    {
      throw new InvalidArgumentException("Invalid 'properties'.");
    }

    /*
     * query involves linked tables
     */
    if ( $targetModel )
    {
      for ( $i=0; $i<2; $i++ )
      {

        /*
         * break if no more properties
         */
        if ( ! isset( $properties[$i] ) ) break;

        /*
         * get model
         */
        switch( $i )
        {
          case 0:
            $alias="t1";
            $m = $model;
            break;
          case 1:
            $alias="t2";
            $m = $targetModel;
            break;
        }

        /*
         * replace "*" with all properties
         */
        if ( $m && $properties[$i]== "*" )
        {
          $properties[$i] = $m->properties();
        }

        /*
         * convert single properties to array
         */
        if ( is_string( $properties[$i] ) )
        {
           $properties[$i] = array( $properties[$i] );
        }

        /*
         * otherwise abort
         */
        elseif ( ! is_array( $properties[$i] ) )
        {
          throw new InvalidArgumentException("Invalid property argument" );
        }

        /*
         * construct column query
         */
        foreach ( $properties[$i] as $property )
        {
          /*
           * skip empty parameters
           */
          if ( ! $property ) continue;

          /*
           * get column name of given property
           */
          $column = $queryBehavior->getColumnName( $property );
          $col = $adpt->formatColumnName( $column );
          //$queryBehavior->info( $model->className() . ": $property -> $col");

          /*
           * alias
           */
          if( isset( $this->as[$property] ) )
          {
            $as = $this->as[$property];
            if ( preg_match('/[^0-9A-Za-z_]/',$as) )
            {
              throw new InvalidArgumentException("Invalid alias '$as'");
            }
          }
          else
          {
            $as = null;
          }

          /*
           * table and column alias
           */
          $str = "$alias.$col";
          if ( $col != $property or $i > 0 )
          {
            if ( $i > 0 )
            {
              $str .= " AS '$relation.$property'";
            }
            elseif ( $as )
            {
              $str .= " AS '$as'";
            }
            elseif( $property != $column )
            {
              $str .= " AS '$property'";
            }
          }
          $columns[] = $str;
        }
      }
    }

    /*
     * query involves only one unlinked table
     */
    else
    {

      /*
       * replace "*" with all properties
       */
      if ( $properties[0] == "*" )
      {
        $properties = $model->properties();
      }
      else
      {
        $properties = $properties[0];
      }


      /*
       * columns, use alias if needed
       */
      $needAlias = false;
      foreach ( $properties as $property )
      {
        if ( ! $property or ! is_string( $property ) )
        {
          throw new InvalidArgumentException("Invalid property argument!");
        }

        $column = $queryBehavior->getColumnName( $property );

        /*
         * alias
         */
        if( isset( $this->as[$property] ) )
        {
          $as = $this->as[$property];
          if ( preg_match('/[^0-9A-Za-z_]/',$as) )
          {
            throw new InvalidArgumentException("Invalid alias '$as'");
          }
        }
        else
        {
          $as = null;
        }

        $str = "\n     " . $adpt->formatColumnName( $column );
        if ( $column != $property )
        {
          $str .= " AS '$property'";
          $needAlias = true;
        }
        elseif ( $as )
        {
          $str .= " AS '$as'";
        }
        $columns[] = $str;
      }
    }


    /*
     * functions in 'select' argument of query
     */
    $functions = array ();
    if (!is_null($selectArgs)) {
        $selectArgs = (array) $selectArgs;
        foreach ($selectArgs as $argument) {
            if (is_array($argument)) {
                if (isset ($argument['function']) && $argument['function'] instanceof qcl_data_db_function_ISqlFunction) {
                    $params = array ();
                    if (isset ($argument['column'])) {
                        $argument['column'] = (array) $argument['column'];
                        foreach ($argument['column'] as $column) {
                            $params[] = $adpt->formatColumnName($column);
                        }
                    }
                    if (isset ($argument['property'])) {
                        $argument['property'] = (array) $argument['property'];
                        foreach ($argument['property'] as $property) {
                            $params[] = $adpt->formatColumnName($this->getColumnName($property));
                        }
                    }
                    try {
                        $functioncall = $argument['function']->toSql($params);
                        if (isset ($argument['as'])) {
                            $as = $argument['as'];
                            if (preg_match('/[^0-9A-Za-z_]/', $as)) {
                                throw new InvalidArgumentException("Invalid alias '$as'");
                            }
                            $functioncall .= " AS '$as'";
                        }
                        $functions[] = "\n     " . $functioncall;
                    } catch (SqlFunctionException $e) {
                        throw new InvalidArgumentException('Invalid arguments for function call. Error: ' . $e->getMessage());
                    }
                }
            } else {
                // ignore, as done before. Why the heck it exists in the query object???
            }
        }
    }

    /*
     * select
     */
    $sql = "\n   SELECT ";

    /*
     * distinct values?
     */
    if ( $this->distinct )
    {
      $sql .= "DISTINCT ";
    }

    /*
     * columns
     */
    if ( $needAlias or count( $properties) != count( $model->properties() ) )
    {
      $sql .= implode(",",  $columns );
    }
    else
    {
      $sql .= " * ";
    }

    // functions
    if (count($functions) != 0) {
        $sql .= ',' . implode(',', $functions);
    }

    /*
     * from
     */
    $thisTable = $adpt->formatTableName( $queryBehavior->getTableName() );
    $sql .= "\n     FROM $thisTable AS t1 ";

    /*
     * join linked records. The "link" property mus be an array
     * of the following structure:
     *
     * array(
     *  'relation' => "name-of-relation"
     * )
     *
     */
    if ( $targetModel )
    {
      $foreignKey   = $adpt->formatColumnName( $model->foreignKey() );
      $targetTable  = $adpt->formatTableName( $targetModel->getQueryBehavior()->getTableName() );
      $targetFKey   = $adpt->formatColumnName( $targetModel->foreignKey() );

      // for now, we do only foreign id
      $foreignId = $link['foreignId'];
      if( ! $foreignId )
      {
        throw new InvalidArgumentException("For now, only foreign id links are allowed.");
      }
      // check foreign id!
      if( ! is_numeric( $foreignId )  )
      {
        throw new InvalidArgumentException("Invalid foreign id '$foreignId'");
      }

      $relType = $relBeh->getRelationType( $relation );

      switch( $relType )
      {
        case QCL_RELATIONS_HAS_ONE:
          $sql .= "\n     JOIN $targetTable AS t2 ON ( t1.id = t2.$foreignKey AND t2.id = $foreignId ) ";
          break;

        case QCL_RELATIONS_HAS_MANY:
          //$sql .= "\n     JOIN $targetTable AS t2 ON ( t1.$targetFKey = t2.id ) ";
          throw new InvalidArgumentException("1:n relations make no sense with foreign id.'");
          break;

        case QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY:

          $joinTable = $adpt->formatColumnName( $relBeh->getJoinTableName( $relation ) );
          $sql .= "\n     JOIN ( $joinTable AS l,$targetTable AS t2) ";
          $sql .= "\n       ON ( t1.id = l.$foreignKey AND l.$targetFKey = t2.id AND t2.id = $foreignId ) ";
          break;

        default:
          // should never get here
          throw new LogicException("Invalid relation type");
      }
    }

    /*
     * construct 'where' statement from the 'where' and
     * 'match' properties of the query object
     */
    if ( $this->where or  $this->match )
    {
      $where = $this->createWhereStatement( $model );
      $sql .= "\n    WHERE $where ";
    }

    /*
     * GROUP BY
     */
    if ( $this->groupBy )
    {
      if ( is_string( $this->groupBy ) )
      {
        $groupBy = explode(",", $this->groupBy );
      }
      else if ( is_array( $this->groupBy ) )
      {
        $groupBy = $this->groupBy;
      }
      else
      {
        throw new InvalidArgumentException("Invalid 'groupBy' data.");
      }

      /*
       * group columns
       */
      $column = array();
      foreach ( $groupBy as $property )
      {
        $column[] = $adpt->formatColumnName( $queryBehavior->getColumnName( $property ) );
      }
      $groupBy = implode(",", (array) $column );
      $sql .= "\n    GROUP BY $groupBy";

    }


    /*
     * ORDER BY
     */
    if ( $this->orderBy )
    {
      if ( is_string( $this->orderBy ) )
      {
        $orderBy = explode(",", $this->orderBy );
      }
      else if ( is_array(  $this->orderBy ) )
      {
        $orderBy =  $this->orderBy;
      } else {
        throw new InvalidArgumentException("Invalid 'orderBy' data.");
      }

      /*
       * order columns
       */
      $column = array();
      foreach ( $orderBy as $property )
      {
        if ( substr( $property, -4 ) == "DESC" )
        {
          $column[] =
            $adpt->formatColumnName(
              $queryBehavior->getColumnName( substr( $property, 0, -5 ) ) ) . " DESC";
        }
        else
        {
          $column[] = $adpt->formatColumnName( $queryBehavior->getColumnName( $property ) );
        }
      }
      $orderBy = implode(",", (array) $column );
      $sql .= "\n    ORDER BY $orderBy";

    }



    /*
     * Retrieve only subset of all rows
     */
    if ( ! is_null( $this->firstRow )
        or ! is_null( $this->lastRow )
        or ! is_null( $this->numberOfRows ) )
    {
      if ( ! is_null( $this->firstRow ) and ! is_null( $this->numberOfRows ) )
      {
        $first  = $this->firstRow;
        $second = $this->numberOfRows;
      }
      elseif ( ! is_null( $this->firstRow ) and ! is_null( $this->lastRow ) )
      {
        $first  = $this->firstRow;
        $second = "$this->lastRow - $this->firstRow"; // since there might be placeholder
      }
      elseif ( ! is_null( $this->numberOfRows ) )
      {
        $first  = $this->numberOfRows;
        $second = null;
      }
      else
      {
        throw new InvalidArgumentException( "Invalid firstRow, lastRow or numberOfRow parameter");
      }
      $sql .=   "\n    " .
      $queryBehavior->getAdapter()->createLimitStatement( $first, $second  );
    }

    return $sql;
  }

  /**
   * Converts data to the 'where' part of a sql statement. If necessary,
   * this will add to the parameter and parameter_types members of the query
   * object.
   *
   * @param qcl_data_model_AbstractActiveRecord $model
   * @throws InvalidArgumentException
   * @return string|null Returns a string if there are conditions that can
   * be expressed in the 'where' query and NULL if not.
   * @todo rewrite to allow other boolean operators
   */
  public function createWhereStatement( qcl_data_model_AbstractActiveRecord $model )
  {
    $queryBehavior = $model->getQueryBehavior();
    $adpt   = $queryBehavior->getAdapter();
    $where  = object2array( $this->getWhere() );
    $match  = object2array( $this->getMatch() );

    /*
     * if we have a string type where statement, return it. Use this with
     * caution, since the string is not sanitized
     * FIXME: Remove this?
     */
    if ( is_string( $where ) )
    {
      return $where;
    }
    elseif ( ! is_array( $where ) and ! is_array( $match ) )
    {
      throw new InvalidArgumentException("Cannot create where query. Invalid query data.");
    }

    /*
     * otherwise create sql from it
     */
    $sql = array();

    /*
     * first use 'where' info
     */
    if( $where)
    {
      foreach( $where as $property => $value )
      {
        //$type   = $model->getPropertyBehavior()->type( $property );
        $column = $adpt->formatColumnName( $queryBehavior->getColumnName( $property ) );
        $param  = ":$property";

        /*
         * null value
         */
        if ( is_null($value) )
        {
          $operator = "IS";
        }

        /*
         * if the value is scalar, use "="
         */
        elseif ( is_scalar($value) )
        {
          $operator = "=";
        }

        /*
         * if an array has been passed, the first element is the
         * operator, the second the value
         */
        elseif ( is_array( $value ) )
        {
          $operator = $value[0];
          $this->checkOperator( $operator );
          $oldValue = $value;
          $value    = $value[1];
        }
        else
        {
          throw new InvalidArgumentException("Property '$property': Invalid value of type " . typeof($value,true) );
        }

        switch(strtoupper($operator)) {
            case 'IN':
                if(! is_array($value)) 
                {
                  // Passing a string is no longer allowed (sql injection hazard)
                  throw new InvalidArgumentException(
                  	"The IN operator can only be used with arrays"
                  );
                }
                $data = array();
                foreach($value as $tmp) 
                {
                  if(is_numeric($tmp)) {
                      $data[] = $tmp;
                  } else {
                      $data[] = '"' . $tmp . '"';
                  }
                }
                $value = implode(', ', $data);
                $sql[] = $column . ' IN(' . $value . ')'; 
                break;
                
            case "BETWEEN":
              $param1 = ":$property" . "_1";
              $param2 = ":$property" . "_2";
              $this->parameters[$param1] = $oldValue[1];
              $this->parameters[$param2] = $oldValue[2];
              $sql[] = $column . " BETWEEN $param1 AND $param2";
              break;

            default:
                $this->parameters[$param] = $value;
                $sql[]  = "$column $operator $param" ;
                break;
        }

      }
    }

    /*
     * now analyse "match" data
     */
    if( $match )
    {
      foreach( $match as $index => $expr )
      {
        // @todo check if index exists, but only from cached data
        //$sql[]  = "MATCH($index) AGAINST '$expr' IN BOOLEAN MODE";
        $sql[]  = $adpt->fullTextSql( $queryBehavior->getTableName(), $index, $expr );
      }
    }

    /*
     * return the result
     */
    if ( count ( $sql ) )
    {
      return implode("\n           AND ", $sql );
    }
    else
    {
      return null;
    }

  }

  /**
   * Converts object to string
   * @return string
   */
  public function __toString()
  {
    return print_r( get_object_vars( $this ), true );
  }
}
?>