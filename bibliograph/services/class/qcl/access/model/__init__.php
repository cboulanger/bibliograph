<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

/*
 * access models
 */
qcl_import( "qcl_access_model_User" );
qcl_import( "qcl_access_model_Role" );
qcl_import( "qcl_access_model_Group" );
qcl_import( "qcl_access_model_Permission" );
qcl_import( "qcl_access_model_Session" );
qcl_import( "qcl_config_ConfigModel" );
qcl_import( "qcl_config_UserConfigModel" );

/*
 * Accept changing IPs. This should only be used during development (i.e. in the
 * cloud) when the requests might originate from different hosts. Never use this
 * in production, since it will allow session takeover
 */
if ( ! defined("QCL_ACCESS_ALLOW_IP_MISMATCH") )
{
  define( "QCL_ACCESS_ALLOW_IP_MISMATCH" ,  false );
}

