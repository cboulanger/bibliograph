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
 * Interface for controllers that support the common
 * CREATE, RETRIEVE, UPDATE, DELETE (CRUD) actions on their models.
 * The necessary authorization has to be insured by the service
 * methods themselves.
 *
 */
interface qcl_data_controller_IItemController
{

  /**
   * Creates a record.
   * @param string $datasource Name of the datasource which will contain
   *   the new record.
   * @param mixed|null $options Optional data that might be
   *   necessary to create the new record
   * @return mixed Id of the newly created record.
   */
  function method_create( $datasource, $options=null );

  /**
   * Retrieves the data of the record with the given id.
   * @param string $datasource Name of the datasource that contains
   *   the record.
   * @param mixed $id Id of the record within the datasource
   * @param mixed|null $options Optional data that might be necessary
   *   to retrieve the record
   * @return array|false Map of key-value pairs containing the data of the
   *   record.
   */
  function method_retrieve( $datasource, $id, $options=null );

  /**
   * Updates the data of the record with the given id.
   * @param string $datasource Name of the datasource that contains
   *   the record.
   * @param mixed $id Id of the record within the datasource
   * @param array $data Map of key-value pairs of the properties
   *   of the model that should be updated.
   * @param mixed|null $options Optional data that might be necessary
   *   to retrieve the record
   * @return boolean True if successful
   */
  function method_update( $datasource, $id, $data, $options=null );

  /**
   * Deletes a record.
   * @param string $datasource Name of the datasource that contains
   *   the record.
   * @param mixed $id Id of the record within the datasource
   * @param mixed|null $options Optional data that might be necessary
   *   to delete the record
   * @return boolean True if successful
   */
  function method_delete( $datasource, $id, $options=null );
}
