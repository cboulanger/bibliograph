/**
 * === Dialog contrib icons ===
 *
 * from the IcoMoon Free Pack
 * https://icomoon.io/#preview-free
 * https://github.com/Keyamoon/IcoMoon-Free
 *
 * Dual-licences under CC BY 4.0 or GPL.
 * see https://github.com/Keyamoon/IcoMoon-Free/blob/master/License.txt
 * http://creativecommons.org/licenses/by/4.0/
 * http://www.gnu.org/licenses/gpl.html
 *
 * @asset(dialog/icon/IcoMoonFree/272-cross.svg)
 * @asset(dialog/icon/IcoMoonFree/273-checkmark.svg)
 * @asset(dialog/icon/IcoMoonFree/264-warning.svg)
 * @asset(dialog/icon/IcoMoonFree/269-info.svg)
 * @asset(dialog/icon/IcoMoonFree/270-cancel-circle.svg)
 * @asset(qx/icon/Oxygen/16/apps/office-calendar.png)
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
 * === Icons from the framework (Tango/Oxygen) ===
 * @asset(qx/icon/${qx.icontheme}/16/actions/address-book-new.png)
 * @asset(qx/icon/${qx.icontheme}/16/actions/application-exit.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/internet-feed-reader.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/internet-transfer.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/preferences-security.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/preferences-users.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/utilities-archiver.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/utilities-graphics-viewer.png)
 * @asset(qx/icon/${qx.icontheme}/16/apps/utilities-network-manager.png)
 * @asset(qx/icon/${qx.icontheme}/16/places/user-trash.png)
 * @asset(qx/icon/${qx.icontheme}/16/status/dialog-password.png)
 * @asset(qx/icon/${qx.icontheme}/22/actions/application-exit.png)
 * @asset(qx/icon/${qx.icontheme}/22/actions/go-next.png)
 * @asset(qx/icon/${qx.icontheme}/22/actions/view-refresh.png)
 * @asset(qx/icon/${qx.icontheme}/22/apps/internet-transfer.png)
 * @asset(qx/icon/${qx.icontheme}/22/apps/preferences-users.png)
 * @asset(qx/icon/${qx.icontheme}/22/apps/utilities-help.png)
 * @asset(qx/icon/${qx.icontheme}/22/categories/system.png)
 * @asset(qx/icon/${qx.icontheme}/22/places/network-server.png)
 *
 */
qx.Theme.define("bibliograph.theme.Icon",
{
  title : "Bibliograph",
  aliases : {
    /** General icon Theme */
    "icon" : "qx/icon/Tango",

    /** Dialog contrib icons */
    "qxl.dialog.icon.cancel" : "dialog/icon/IcoMoonFree/272-cross.svg",
    "qxl.dialog.icon.ok"     : "dialog/icon/IcoMoonFree/273-checkmark.svg",
    "qxl.dialog.icon.info"   : "dialog/icon/IcoMoonFree/269-info.svg",
    "qxl.dialog.icon.error"  : "dialog/icon/IcoMoonFree/270-cancel-circle.svg",
    "qxl.dialog.icon.warning" : "dialog/icon/IcoMoonFree/264-warning.svg"
  }
});
