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

qcl_import("qcl_data_model_IActiveRecord");

/**
 * Like qcl_data_model_IActiveRecord, but provides
 * methods that add a "named id" to the model, i.e. a unique
 * string-type name that identifies the model locally or globally,
 * as opposed to the numeric id which is specific to the table.
 */
interface qcl_data_model_INamedActiveRecord
//  extends qcl_data_model_IActiveRecord
{
  /**
   * Returns the named id if it exists as property
   * @return string
   */
  public function getNamedId();

  /**
   * Sets the named id if it exists as property
   * @param $namedId
   * @return string
   */
  public function setNamedId( $namedId );

  /**
   * Creates a new model record.
   * @param $namedId
   * @return int Id of the record
   */
  public function create( $namedId );

  /**
   * Creates a new model record if one with the given named id does
   * not already exist.
   * @param string  $namedId
   * @return int the id of the inserted or existing record
   */
  public function createIfNotExists( $namedId );

  /**
   * Checks if a model with the given named id exists.
   * @param $namedId
   * @return int id of record or false if does not exist
   */
  public function namedIdExists( $namedId );

  /**
   * Loads a model record by numeric id or string-type named id.
   * Throws an error if record does not exist.
   *
   * @param string|int $id
   * @return array Record data
   * @throws qcl_data_model_RecordNotFoundException
   */
  public function load( $id );
}
