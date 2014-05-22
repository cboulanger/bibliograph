<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

interface bibliograph_webapis_IWebApi
{

  /**
   * Returns the name of the Web API
   * @return string
   */
  function getName();

  /**
   * Returns the description of the Web API
   * @return string
   */
  function getDescription();

  /**
   * Returns one or several categories this API belongs to
   * @return array
   */
  function getCategories();
}