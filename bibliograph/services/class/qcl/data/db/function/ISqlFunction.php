<?php
/**
 * <code>ISqlFunction</code>
 *
 * Interface for SQL function implementations
 *
 * Implementations are currently just format helpers to avoid several
 * changes in QueryBehavior
 *
 * @package qcl_data_db_function
 * @experimental
 *
 * @author Oliver Friedrich <oliver@familie-friedrich.de>
 * @copyright &copy; 2010 Oliver Friedrich
 * @version $Id: ISqlFunction.php 20545 2010-07-15 12:08:46Z jesus77 $
 */

/**
 * <code>ISqlFunction</code>
 *
 * Interface for SQL function implementations
 *
 * Implementations are currently just format helpers to avoid several
 * changes in QueryBehavior
 *
 * @package qcl_data_db_function
 * @experimental
 *
 * @author Oliver Friedrich <oliver@familie-friedrich.de>
 * @copyright &copy; 2010 Oliver Friedrich
 * @version 1.0
 */
interface qcl_data_db_function_ISqlFunction {

    /**
     * Implementations must use the singleton pattern
     * @return qcl_data_db_function_ISqlFunction
     */
    public static function getInstance();

    /**
     * builds the formated function call
     *
     * The $properties parameter can be a single column or an array of columns,
     * if the implemented function supports it.
     *
     * @param string|string[] $properties Parameter to include inside of the Sql
     * function
     * @return string formated function call
     */
	public function toSql($properties);
}

