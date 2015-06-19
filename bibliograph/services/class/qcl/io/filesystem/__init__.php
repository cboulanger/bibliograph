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

/*
 * exceptions
 */
class qcl_io_filesystem_Exception extends LogicException{}
class qcl_io_filesystem_FileExistsException extends qcl_io_filesystem_Exception{}
class qcl_io_filesystem_FileNotFoundException extends qcl_io_filesystem_Exception{}
class qcl_io_filesystem_NoFileLoadedException extends qcl_io_filesystem_Exception{}

/*
 * log filters
 */
define("QCL_LOG_FILESYSTEM","filesystem");
qcl_log_Logger::getInstance()->registerFilter( QCL_LOG_FILESYSTEM, "Messages concerning filesystems", false);
