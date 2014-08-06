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

/**
 * Interface for classes that implement configuration management
 *
 * the idea of the class is the following:
 * - configuration entries (properties) have a name(key), a type, and a value
 * - types accepted currently are string, number and boolean (others might follow
 *   if there is a need)
 * - for each configuration entry, you can also set a read and a write permission.
 *   if set, the active user requesting an action must have the corresponding
 *   permission.
 * - each configuration entry can also have a user variant, i.e. users can,
 *   if allowed, create their own versions of the entry. When they edit
 *   this entry, they will all other variants will be left untouched including
 *   the default value, to which the user variant can be reset.
 * - on the client, a qcl.config.Manager singleton object takes care of retrieval
 *   and update of the config entries. on login, all or a subset of the configuration
 *   entries that an individual user has access to will be sent to the client and
 *   cached there.
 *
 *   @todo
 */

interface qcl_config_IConfigModel
{
  /**
   * Returns singleton instance.
   * @return qcl_config_DbModel
   */
  public static function getInstance();

  /**
   * Creates a config property, overwriting any previous entry
   * requires permission "qcl.config.permissions.manage"
   *
   * @param $namedId
   * @param string $type The type of the property (string|number|object|boolean)
   * @param boolean $allowUserVariants If true, allow users to create their
   *      own variant of the configuration setting
   * @internal param string $nameId The name of the property (i.e., myapplication.config.locale)
   * @return id of created config entry
   */
  public function createKey( $namedId, $type, $allowUserVariants=false );

  /**
   * Create a config key if it doesn't exist already
   * @see qcl_config_DbModel::create()
   * @param $namedId
   * @param $type
   * @param $allowUserVariants
   * @return int|bool  Returns the id of the newly created record, or false if
   * key was not created.
   */
  public function createKeyIfNotExists( $namedId, $type, $allowUserVariants=false );

  /**
   * Returns config property value. Raises an error if key does not exist.
   * @param string $namedId The name of the property (i.e., myapplication.config.locale)
   * @param bool $userId
   * @internal param $ int|null[optional] $userId Optional user id. If not given, get the config
   * key for the current user.
   * @return value of property.
   */
  public function getKey( $namedId, $userId=false );

  /**
   * Checks if the config entry exists (optional: for a specific user)
   * @param $nameId
   * @param int $userId
   * @return
   * @internal param string $name
   */
  public function hasKey( $nameId, $userId=null );

  /**
   * Sets config property
   * @param string $namedId The name of the property (i.e., myapplication.config.locale)
   * @param string $value The value of the property.
   * @param int|boolean $userId[optional] If 0, set the default value
   * @return true if success or false if there was an error
   */
  public function setKey( $namedId, $value, $userId=false);

  /**
   * Deletes a config key dependent on permissions
   * @todo call statically with id parameters
   * @param null $ids
   * @return void
   */
  public function deleteKey( $ids=null );

  /**
   * Delete all records that belong to a userId
   * @param int $userId
   * @return void
   */
  public function deleteByUserId( $userId );

  /**
   * Returns the type of the current config record
   * @return string
   */
  public function getType();

  /**
   * Returns the value of the current record in the correct variable type
   * @return mixed $value
   */
  public function getValue();

  /**
   * Sets a default value for a config key
   * @param $namedId
   * @param $value
   * @return void
   */
  public function setDefault( $namedId, $value );

  /**
   * Gets the default value for a config key
   * @param $namedId
   * @return mixed
   */
  public function getDefault( $namedId );

  /**
   * Resets the user variant of a config value to the default value.
   * @param $namedId
   * @return void
   */
  public function reset( $namedId );


  /**
   * Returns all config property value that are readable by the active user
   * @param string $mask return only a subset of entries that start with $mask
   * @return array Array
   */
  public function getAccessibleKeys( $mask=null );
}
