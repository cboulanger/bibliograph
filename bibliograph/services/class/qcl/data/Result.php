<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

/**
 * The base class for all classes that are used as the result of a
 * service method. Works as a "marker interface" for data results
 * and provides serialization methods.
 */
class qcl_data_Result
  extends qcl_core_Object
{

  /**
   * Constructor. Optionally presets the object property values
   * @param array|null $data
   * @return \qcl_data_Result
   */
  function __construct( $data=null )
  {
    if ( is_array( $data ) )
    {
      foreach( $data as $key => $value )
      {
        $this->createProperty( $key, array(
          'init' => $value
        ) );
      }
    }
  }

  /**
   * Converts a query result set into a model in which the properties
   * contain all the values of the properties in the result set
   * as arrays. Returns the converted object;
   * @param $data
   * @return qcl_data_Result
   */
  function queryResultToModel( $data )
  {
    if ( ! is_array( $data ) )
    {
      trigger_error("Invalid argument");
    }

    foreach ( $data as $record )
    {
      foreach ( $record as $key => $value )
      {
        $values = (array) $this->get( $key );
        array_push( $values, $value );
        $this->set( $key, $values);
      }
    }
    return $this;
  }
}
