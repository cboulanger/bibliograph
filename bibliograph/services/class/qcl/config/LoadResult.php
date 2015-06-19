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
qcl_import("qcl_data_Result");

/**
 * @todo rename to qcl_config_LoadResult
 *
 */
class qcl_config_LoadResult
  extends qcl_data_Result
{
   /**
    * The config keys
    * @var array
    */
   public $keys;

   /**
    * The config values
    * @var array
    */
   public $values;

   /**
    * The config types
    * @var array
    */
   public $types;

}
