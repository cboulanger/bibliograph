<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

/**
 * Routes mapping JSONRPC services to class names
 */
return array(
  "bibliograph.access"     => "bibliograph_service_Access",
  "bibliograph.config"     => "qcl_config_Service",
  "bibliograph.plugin"     => "qcl_application_plugin_Service",
  "bibliograph.model"      => "bibliograph_service_Model",
  "bibliograph.folder"     => "bibliograph_service_Folder",
  "bibliograph.reference"  => "bibliograph_service_Reference",
  "bibliograph.main"       => "bibliograph_service_Application",
  "bibliograph.import"     => "bibliograph_service_Import",
  "bibliograph.export"     => "bibliograph_service_Export",
  "bibliograph.backup"     => "bibliograph_service_Backup",
  "bibliograph.actool"     => "bibliograph_service_ACLTool",
  "bibliograph.setup"      => "bibliograph_service_Setup"
);