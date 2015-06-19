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

qcl_import("qcl_util_registry_Session");

/**
 * Class which maintains a registry which is valid during one page load.
 * For this to work, there must be a service method with is requested only
 * once during the application startup and which calls
 * qcl_util_registry_PageLoad::reset()
 */
class qcl_util_registry_PageLoad
  extends qcl_util_registry_Session
{
  const KEY = "QCL_UTIL_REGISTRY_PAGELOAD_KEY";
}
