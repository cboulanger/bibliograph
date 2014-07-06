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
 */

qcl_import( "qcl_data_model_AbstractActiveRecord" );
qcl_import( "qcl_data_model_INamedActiveRecord" );

/**
 * Like qcl_data_model_AbstractActiveRecord, but provides
 * methods that add a "named id" to the model, i.e. a unique
 * string-type name that identifies the model locally or globally,
 * as opposed to the numeric id which is specific to the table.
 * @todo constructor and properties are missing
 */
abstract class qcl_data_model_AbstractNamedActiveRecord
  extends qcl_data_model_AbstractActiveRecord
{

  /**
   * Getter for named id
   * @alias getNamedId
   * @return string
   */
  public function namedId()
  {
    return $this->_get("namedId");
  }

  /**
   * Getter for named id
   * @return string
   */
  public function getNamedId()
  {
    return $this->_get("namedId");
  }

  /**
   * Setter for named id
   * @param string $value
   * @return qcl_data_model_AbstractNamedActiveRecord Returns itself for method chaining
   */
  public function setNamedId($value)
  {
    qcl_assert_valid_string($value);
    return $this->_set("namedId",$value);
    return $this;
  }

  /**
   * Creates a new model record, optionally setting initial
   * property values.
   *
   * @param string|array|object $first If string, use as named id. If array,
   * or object, use as record data to extract the named id from.
   * @param array|null Optional map of properties to set
   * @throws qcl_data_model_RecordExistsException
   * @throws InvalidArgumentException
   * @return int Id of the record
   */
  public function create( $first=null, $data=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * mark that record is loaded
     */
    $this->_loaded = true;

    /*
     * check named id
     */
    if ( is_array( $first ) or is_object( $first ) )
    {
      $data = (array) $first;
      $namedId = $data['namedId'];
      unset( $data['namedId'] );
    }
    elseif ( is_string( $first ) )
    {
      $namedId = $first;
    }
    else
    {
      throw new InvalidArgumentException("Invalid named id '$first'" );
    }

    /*
     * check for duplicate
     */
    if ( isset( $this->__namedIdExistChecked ) )
    {
      unset( $this->__namedIdExistChecked );
    }
    elseif ( $id=$this->namedIdExists( $namedId ) )
    {
      throw new qcl_data_model_RecordExistsException("Named id '$namedId' already exists");
    }

    /*
     * reset properties to default values
     */
    $this->getPropertyBehavior()->initPropertyValues();
    $this->set("namedId", $namedId );
    $this->set("created", new qcl_data_db_Timestamp("now") );

    if( is_array( $data ) )
    {
      $this->set( $data );
    }

    /*
     * insert into database
     */
    $id = (int) $this->getQueryBehavior()->insertRow( $this->data() );

    /*
     * reload the data, in case the database has changed/added something
     */
    $this->load( $id );

    /*
     * log message
     */
    if ( $this->getLogger()->isFilterEnabled ( QCL_LOG_MODEL ) )
    {
      $this->log( sprintf( "Created new model record '%s'.", $this ), QCL_LOG_MODEL );
    }

    /*
     * increment transaction id since data has changed
     */
    $this->incrementTransactionId();

    /*
     * fire event
     */
    $this->fireDataEvent("change", array(
      'start' => $id,
      'end'   => $id,
      'type'  => "add",
      'items' => array( $id )
    ));
    $this->fireEvent("changeLength");

    /*
     * return the id
     */
    return $id;
  }

  /**
   * Creates a new model record if one with the given named id does
   * not already exist.
   *
   * @param string  $namedId
   * @param array $data
   *    An option array of the properties that should be set in the model
   * @return int
   *    The id of the inserted or existing record
   * @todo
   *    This could be optimized to avoid queries
   */
  public function createIfNotExists( $namedId, $data=array()  )
  {
    /*
     * initialize if not already initialized
     */
    $this->init();

    /*
     * get numeric id and load record, if not exists, create it
     */
    $id = $this->namedIdExists( $namedId );
    if ( $id !== false )
    {
      $this->load( $id );
    }
    else
    {
      $this->__namedIdExistChecked = true;
      $id = $this->create( $namedId, $data );
    }
    return $id;
  }

  /**
   * Checks if a model with the given named id exists.
   * @param $namedId
   * @return int id of record or false if does not exist
   */
  public function namedIdExists( $namedId )
  {
    $this->init();

    $bhv = $this->getQueryBehavior();
    $ids = $bhv->fetchValues( "id", array( 'namedId' => $namedId ) ); 
    if ( count( $ids ) )
    {
      return (int) $ids[0];
    }
    else
    {
      return false;
    }
  }

  /**
   * Loads a model record by numeric id or string-type named id.
   * Returns itself to allow chained method calling.
   * @param string|int $id
   * @throws qcl_data_model_RecordNotFoundException
   * @throws InvalidArgumentException
   * @return qcl_data_model_AbstractNamedActiveRecord
   */
  public function load( $id )
  {
    if ( is_string( $id ) )
    {
      /*
       * initialize, if necessary
       */
      $this->init();

      /*
       * load record
       */
      $this->getQueryBehavior()->selectWhere( array( "namedId" => $id ) );
      $result = $this->getQueryBehavior()->fetch();
      if ( $result )
      {
        $propBehavior = $this->getPropertyBehavior();
        foreach( $result as $property => $value )
        {
          $this->set( $property, $propBehavior->typecast( $property, $value ), false );
        }

        /*
         * Mark that we're loaded
         */
        $this->_loaded = true;

        /*
         * save a copy so we can check whether properties have changed
         */
        $this->_data = $this->data();

        /*
         * return myself
         */
        return $this;
      }

      /*
       * throw exception if record doesn't exist
       */
      else
      {
        throw new qcl_data_model_RecordNotFoundException( sprintf(
          "Named model record [%s #%s] does not exist",
          $this->className(), $id
        ) );
      }
    }
    elseif ( is_numeric( $id ) )
    {
      return parent::load( $id );
    }
    throw new InvalidArgumentException("Invalid namedId argument.");
  }

  /**
   * Return a string representation of the model
   */
  public function __toString()
  {
    $namedId = $this->getPropertyBehavior()->_get("namedId");
    $ds = $this->datasourceModel();
    return sprintf( "[%s%s/%s]", ( $ds ? $ds->namedId() . "/" : "" ) , $this->className(),  ( $namedId ? $namedId : "--" ) );
  }
}