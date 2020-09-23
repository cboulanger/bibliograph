/**
 *
 * === Small menu buttons ===
 * @asset(bibliograph/icon/button-settings-up.png)
 * @asset(bibliograph/icon/button-plus.png)
 * @asset(bibliograph/icon/button-minus.png)
 * @asset(bibliograph/icon/button-edit.png)
 * @asset(bibliograph/icon/button-reload.png)
 * @asset(bibliograph/icon/button-mail.png)
 *
 * === other icons ===
 * @asset(bibliograph/icon/16/cancel.png)
 * @asset(bibliograph/icon/16/help.png)
 * @asset(bibliograph/icon/16/search.png)
 *
 * === Icons from the framework (Tango) ===
 * @asset(qx/icon/Tango/16/actions/address-book-new.png)
 * @asset(qx/icon/Tango/16/actions/application-exit.png)
 * @asset(qx/icon/Tango/16/apps/internet-feed-reader.png)
 * @asset(qx/icon/Tango/16/apps/internet-transfer.png)
 * @asset(qx/icon/Tango/16/apps/preferences-security.png)
 * @asset(qx/icon/Tango/16/apps/preferences-users.png)
 * @asset(qx/icon/Tango/16/apps/utilities-archiver.png)
 * @asset(qx/icon/Tango/16/apps/utilities-graphics-viewer.png)
 * @asset(qx/icon/Tango/16/apps/utilities-network-manager.png)
 * @asset(qx/icon/Tango/16/places/user-trash.png)
 * @asset(qx/icon/Tango/16/status/dialog-password.png)
 * @asset(qx/icon/Tango/22/actions/application-exit.png)
 * @asset(qx/icon/Tango/22/actions/go-next.png)
 * @asset(qx/icon/Tango/22/actions/view-refresh.png)
 * @asset(qx/icon/Tango/22/apps/internet-transfer.png)
 * @asset(qx/icon/Tango/22/apps/preferences-users.png)
 * @asset(qx/icon/Tango/22/apps/utilities-help.png)
 * @asset(qx/icon/Tango/22/categories/system.png)
 * @asset(qx/icon/Tango/22/places/network-server.png)
 *
 */
qx.Theme.define("bibliograph.theme.Icon",
{
  extend: qxl.dialog.theme.icon.IcoMoonFree,
  title : "Bibliograph Icon Theme",
  aliases : {
    /** General icon Theme, overrides qxl icon alias */
    "icon" : "qx/icon/Tango"
  }
});
