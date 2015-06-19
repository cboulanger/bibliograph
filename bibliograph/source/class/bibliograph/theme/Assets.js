/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */
/*global bibliograph qx */

/**
 * @asset(qx/icon/${qx.icontheme}/16/actions/address-book-new.png)
 * @asset(qx/icon/${qx.icontheme}/16/actions/application-exit.png)
 * @asset(qx/icon/${qx.icontheme}/16/actions/dialog-ok.png)
 * @asset(qx/icon/${qx.icontheme}/16/actions/document-open.png)
 * @asset(qx/icon/${qx.icontheme}/16/actions/document-save.png)
 * @asset(qx/icon/${qx.icontheme}/16/actions/edit-delete.png)
 * @asset(qx/icon/${qx.icontheme}/16/actions/edit-find.png)
 * @asset(qx/icon/${qx.icontheme}/16/actions/help-about.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/utilities-graphics-viewer.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/internet-transfer.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/internet-feed-reader.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/preferences-users.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/preferences-security.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/preferences-security.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/utilities-archiver.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/utilities-network-manager.png)
 * @asset(qx/icon/${qx.icontheme}/16/places/folder.png)
 * @asset(qx/icon/${qx.icontheme}/16/places/folder-open.png)
 * @asset(qx/icon/${qx.icontheme}/16/places/folder-remote.png)
 * @asset(qx/icon/${qx.icontheme}/16/places/user-trash.png)
 * @asset(qx/icon/${qx.icontheme}/16/places/user-trash-full.png)
 * @asset(qx/icon/${qx.icontheme}/16/status/dialog-information.png)
 * @asset(qx/icon/${qx.icontheme}/16/status/dialog-password.png)
 * @asset(qx/icon/${qx.icontheme}/16/status/dialog-warning.png)
 * @asset(qx/icon/${qx.icontheme}/22/actions/dialog-ok.png)
 * @asset(qx/icon/${qx.icontheme}/22/apps/preferences-users.png)
 * @asset(qx/icon/${qx.icontheme}/22/apps/preferences-security.png)
 * @asset(qx/icon/${qx.icontheme}/22/apps/utilities-archiver.png)
 * @asset(qx/icon/${qx.icontheme}/22/apps/utilities-help.png)
 * @asset(qx/icon/${qx.icontheme}/22/apps/internet-transfer.png)
 * @asset(qx/icon/${qx.icontheme}/22/actions/dialog-cancel.png)
 * @asset(qx/icon/${qx.icontheme}/22/categories/system.png)
 * @asset(qx/icon/${qx.icontheme}/22/places/network-server.png)
 * @asset(qx/icon/${qx.icontheme}/32/status/dialog-error.png)
 * @asset(qx/icon/${qx.icontheme}/32/status/dialog-information.png)
 * @asset(qx/icon/${qx.icontheme}/32/status/dialog-warning.png)
 * @asset(qx/icon/${qx.icontheme}/48/status/dialog-information.png)
 */
qx.Class.define("bibliograph.theme.Assets",
{
  extend : qx.core.Object,
  members : {
    /**
     * dummy function to mark messages for translation until we
     * have a system for plugins and contributions
     */
    dummy : function()
    {
      /*
       * csl plugin
       */
      this.tr('Print')
      this.tr("Style");
      this.tr("All in folder");
      this.tr('Tabular View');
      this.tr('Formatted View');
      this.tr("Generate formatted citations ...");

      /*
       * virtualdata
       */
      this.tr("Loading rows %1 - %2 of %3 ...");
      this.tr("Getting number of rows...");
    }
  }
});
