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

interface bibliograph_webapis_identity_IIdentity
{

  /**
   * @return string
   * @throws LogicException
   */
  function getDescription();

  /**
   * If the name is unique, return the sortable version, normally: last/family name, first name(s).
   * If there is no exact match, return an array of possible names.
   * If no match exists, return false
   * @param string $name
   * @return false|string|array
   */
  function getSortableName($name);
}