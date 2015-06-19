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
   * Configuration keys to be created at application startup if they do not already
   * exist.
   */
  return array(
    "application.title" => array(
      "type"      => "string",
      "custom"    => false,
      "default"   => "Bibliograph Online Bibliographic Data Manager",
      "final"     => false
    ),
    "application.logo" => array(
      "type"      => "string",
      "custom"    => false,
      "default"   => "bibliograph/icon/bibliograph-logo.png",
      "final"     => false
    ),
    "bibliograph.access.mode" => array(
      "type"      => "string",
      "custom"    => false,
      "default"   => "normal",
      "final"     => false
    ),
    "bibliograph.access.no-access-message" => array(
      "type"      => "string",
      "custom"    => false,
      "default"   => "",
      "final"     => false
    ),
    "bibliograph.duplicates.threshold" => array(
      "type"      => "number",
      "custom"    => true,
      "default"   => 50,
      "final"     => false
    ),
    // TODO: remove this
    "plugin.csl.bibliography.maxfolderrecords" => array(
      "type"      => "number",
      "custom"    => false,
      "default"   => 500,
      "final"     => false
    )
  );