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

qcl_import("qcl_data_model_db_ActiveRecord");

class qcl_application_plugin_RegistryModel
  extends qcl_data_model_db_NamedActiveRecord
{
  protected $tableName = "data_Plugin";

  private $properties = array(
    'name'  => array(
      'check'       => "string",
      'sqltype'     => "varchar(100)"
    ),
    'description'  => array(
      'check'       => "string",
      'sqltype'     => "varchar(250)"
    ),
    'data'  => array(
      'check'       => "array",
      'sqltype'     => "text NULL default NULL",
      'init'        => array(),
      'serialize'   => true
    ),
    'active'      => array(
      'check'       => "boolean",
      'sqltype'     => "int(1)",
      'nullable'    => false,
      'init'        => true
    )
  );

  /**
   * Constructor, adds properties
   * @return \qcl_application_plugin_RegistryModel
   */
  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
  }
}
