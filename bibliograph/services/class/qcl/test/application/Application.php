<?php
/* ************************************************************************

   qcl - the qooxdoo component library

   http://qooxdoo.org/contrib/project/qcl/

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import("qcl_application_Application");
qcl_import("qcl_test_access_TestController");

/**
 * Main application class
 */
class qcl_test_application_Application
  extends qcl_application_Application
{

  /**
   * The id of the application, usually the namespace
   * @var string
   */
  protected $applicationId = "test";

  /**
   * The descriptive name of the application
   * @var string
   */
  protected $applicationName = "Test Application";

  /**
   * The version of the application. The version will be automatically replaced
   * by the script that creates the distributable zip file. Do not change.
   * @var string
   */
  protected $applicationVersion = "master";

  /**
   * The default datasource schema used by the application
   * @var string
   */
  protected $defaultSchema = "test";

  /**
   * Returns the singleton instance of this class. Note that
   * qcl_application_Application::getInstance() stores the
   * singleton instance for global access
   * @return bibliograph_Application
   */
  static public function getInstance()
  {
    return parent::getInstance();
  }
  
  /**
   * Getter for access controller.
   * @return qcl_test_access_TestController
   */  
  public function getAccessController()
  {
    return qcl_test_access_TestController::getInstance();
  }
}