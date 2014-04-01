<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
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
 * Interface for query behaviors
 */
interface qcl_data_model_IQueryBehavior
{

  //-------------------------------------------------------------
  // Constructor
  //-------------------------------------------------------------

  /**
   * Constructor
   * @param qcl_data_model_IActiveRecord $model Active record model affected by this behavior
   */
  function __construct( $model );

  //-------------------------------------------------------------
  // Getters and setters for properties
  //-------------------------------------------------------------

  /**
   * Getter for model affected by this behavior
   * @return qcl_data_model_db_ActiveRecord
   */
  public function getModel();

  /**
   * Retrieves the datasource object
   * @return qcl_data_datasource_DbModel
   */
  public function getDatasourceModel();

  /**
   * Getter for table name
   * @return string
   */
  public function getTableName();

  //-------------------------------------------------------------
  // Database management
  //-------------------------------------------------------------

  /**
   * Getter for database manager singleton object
   * @return qcl_data_db_Manager
   */
  public function getManager();

  /**
   * Returns the database connection adapter for this model, which is
   * taken from the datasource object or from the framework.
   * @return qcl_data_db_adapter_IAdapter
   */
  public function getAdapter();

  //-------------------------------------------------------------
  // Table management
  //-------------------------------------------------------------

  /**
   * Returns the prefix for tables used by this
   * model. defaults to the datasource name plus underscore
   * or an empty string if there is no datasource
   * @return string
   */
  public function getTablePrefix();

  /**
   * Returns the table object used by this behavior
   * @return qcl_data_db_Table
   */
  public function getTable();

  /**
   * Returns the name of the column that holds the unique (numeric) id of the table.
   * @return string
   */
  public function getIdColumn();

  /**
   * Returns the column name from a property name. By default, return the
   * property name. Override for different behavior.
   * @return string
   * @param $name
   * @internal param string $property Property name
   */
  public function getColumnName( $name );

  /**
   * Add properties to the primary index of the model
   *
   * @param string[] $properties Array of the property names of the model that should be inserted into the primary key
   * @since 2010-05-21
   */
   public function addPrimaryIndexProperties(array $properties);


  /**
   * Gets the properties for the primary index
   *
   * @return string[]
   */
  public function getPrimaryIndexProperties();

  //-------------------------------------------------------------
  // Record search and retrieval methods (select/fetch methods)
  //-------------------------------------------------------------

  /**
   * Converts a qcl_data_db_Query object to an sql statement
   * @param qcl_data_db_Query $query
   * @return string sql statement
   */
  public function queryToSql( qcl_data_db_Query $query);

  /**
   * Converts data to the 'where' part of a sql statement. If necessary,
   * this will add to the parameter and parameter_types members of the query
   * object.
   *
   * @param qcl_data_db_Query $query
   * @return string
   */
  public function createWhereStatement( qcl_data_db_Query $query );

  /**
   * Runs a query on the table managed by this behavior.
   * @param qcl_data_db_Query $query
   * @return int number of rows selected
   */
  public function select( qcl_data_db_Query $query);

  /**
   * Selects all database records or those that match a where condition.
   * Takes a qcl_data_db_Query object or an array as argument. If an array
   * is passed, a new qcl_data_db_Query object is created and its 'where'
   * property populated with the array.
   * @param qcl_data_db_Query|array $query
   * @see qcl_data_db_Query
   * @return int number of rows selected
   */
  public function selectWhere( $query );

  /**
   * If no argument, return the first or next row of the result of the previous
   * query. If a query object is passed, return the first or next row of the
   * result of this query.
   * The returned value is converted into the correct type
   * according to the property definition and the property behavior.
   * @see qcl_data_model_db_PropertyBehavior::typecast()
   * @param qcl_data_db_Query|null $query
   * @return array
   */
  public function fetch( $query = null );

  /**
   * If no argument, return all rows of the result of the previous
   * query. If a query object is used as argument, run this query beforehand and
   * return the result. Don't use this for large amounts of data.
   * @param qcl_data_db_Query $query
   * @return array
   */
  public function fetchAll( $query = null );


  /**
   * Returns all values of a model property that match a query
   * @param string $property Name of property
   * @param qcl_data_db_Query|array $query
   * @return array Array of values
   */
  public function fetchValues( $property, $query );

  /**
   * Returns a records by property value
   * @param string $propName Name of property
   * @param string|array $values Value or array of values to find. If an array, retrieve all records
   * that match any of the values.
   * @param qcl_data_db_Query|null $query
   * @return array recordset
   */
  public function selectBy( $propName, $values, $query=null );

  /**
   * Select an array of ids for fetching
   * @param array $ids
   * @return void
   */
  public function selectIds( $ids );

  /**
   * Returns the number of records found in the last query.
   * @return int
   */
  public function rowCount();

  /**
   * Counts records in a table matching a where condition.
   * @param string|array  $where where condition
   * @return int
   */
  public function countWhere( $where );

  /**
   * Returns the number of records in the table
   * @return int
   */
  public function countRecords();

  //-------------------------------------------------------------
  // Data creation and manipulation
  //-------------------------------------------------------------

  /**
   * Inserts a data record.
   * @param array $data
   * @return int The id of the created row.
   */
  public function insertRow( $data );

  /**
   * Updates a record in a table identified by id
   * @param array $data associative array with the column names as keys and
   *  the column data as values.
   * @param int|string  $id  if the id key is not provided in the $data
   *  paramenter, provide it here (optional)
   * @param bool $keepTimestamp If true, do not overwrite the 'modified'
   *  timestamp
   * @return boolean success
   */
  public function update ( $data, $id=null, $keepTimestamp= false );

  /**
   * Update the records matching the where condition with the key-value pairs
   * @param array $data
   * @param string|array $where
   * @return result
   */
  public function updateWhere( $data, $where );

  /**
   * Deletes one or more records in a table identified by id
   * @param array|int $ids (array of) record id(s)
   */
  public function deleteRow ( $ids );

  /**
   * Deletes one or more records in a table matching a where condition.
   * This does not delete dependencies!
   * @param string  $where where condition
   * @return void
   */
  public function deleteWhere ( $where );

    /**
   * Resets any internal data the behavior might keep
   * @return void
   */
  public function reset();
}
?>