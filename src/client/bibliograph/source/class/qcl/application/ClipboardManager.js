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
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

/**
 * This clipboard singleton manages all clipboard operations of the application
 */
qx.Class.define('qcl.application.ClipboardManager',
{
  
  extend: qx.core.Object,
  type: 'singleton',
  include: [qcl.application.MWidgetId],
  
  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */
  
  construct: function () {
    this.base(arguments)
    this.setWidgetId('app/clipboard');
    this.__data = {}
    this.__actions = {}
    let vActions = ['move', 'copy', 'alias', 'nodrop']
    this.info("Clipboard ready.");
  },
  
  /*
  *****************************************************************************
     EVENTS
  *****************************************************************************
  */
  events:
  {
    /**
     * This event informs about a change in the clipboard data. The event
     * data is the mime type of the data that has changed, or null if the
     * clipbard has been cleared.
     */
    'changeData': 'qx.event.type.Data'
  },
  
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  
  properties:
  {
    sourceWidget:
    {
      check: 'qx.ui.core.Object',
      nullable: true,
      event: 'changeSourceWidget'
    },
    
    currentAction:
    {
      check: 'String',
      nullable: true,
      event: 'changeCurrentAction'
    }
    
  },
  
  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  
  members:
  {
    
    /*
    ---------------------------------------------------------------------------
      DATA HANDLING
    ---------------------------------------------------------------------------
    */
    
    /**
     * Add data of mimetype.
     * @param vMimeType {String} mime type
     * @param vData {String} data to be added
     * @param doNotClear {Boolean} if set, add data to clipboard without replacing existing data
     * @return {qcl.application.ClipboardManager}
     */
    addData: function (vMimeType, vData, doNotClear = false)
    {
      if (!doNotClear) {
        this.clearData()
      }
      this.__data[vMimeType] = vData
      this.fireDataEvent('changeData', vMimeType);
      return this;
    },
  
    /**
     * Return the mimetypes for which the clipboard has data
     * @return {string[]}
     */
    getMimeTypes : function()
    {
      return Object.getOwnPropertyNames(this.__data).filter(
        prop => !! this.__data[prop]
      );
    },
    
    /**
     * @param vMimeType {String}
     * @return {String|null}
     */
    getData: function (vMimeType) {
      return this.__data[vMimeType]
    },
    
    /**
     * @param vMimeType {String}
     * @return {Boolean}
     */
    hasData: function (vMimeType) {
      return !!this.__data[vMimeType]
    },
    
    /**
     * @return {void}
     */
    clearData: function () {
      this.__data = {}
      this.fireDataEvent('changeData', null);
    },
    
    /*
    ---------------------------------------------------------------------------
      ACTION HANDLING
    ---------------------------------------------------------------------------
    */
    
    /**
     * @param vAction {var} TODOC
     * @param vForce {var} TODOC
     * @return {void}
     */
    addAction: function (vAction, vForce) {
      this.__actions[vAction] = true
      // Defaults to first added action
      if (vForce || this.getCurrentAction() == null) {
        this.setCurrentAction(vAction)
      }
    },
    
    /**
     * Clear actions
     * @return {void}
     */
    clearActions: function () {
      this.__actions = {}
      this.setCurrentAction(null)
    },
    
    /**
     * Set the current action
     * @param vAction {var} TODOC
     * @return {void}
     */
    setAction: function (vAction) {
      if (vAction != null && !(vAction in this.__actions)) {
        this.addAction(vAction, true)
      } else {
        this.setCurrentAction(vAction)
      }
    },
    
    /**
     * Tries to copy text to the clipboard of the underlying operating system
     * and alerts if not successful
     * @param text {String}
     * @param flavor {String}
     */
    copyToSystemClipboard: function (text, flavor) {
      try {
        this._copyToSystemClipboard(text, flavor)
      } catch (e) {
        alert(e)
      }
    },
    
    /**
     * Tries to copy text to the clipboard of the underlying operating system
     * and returns false if not successful
     * @param text {String}
     * @param flavor {String}
     */
    tryCopyToSystemClipboard: function (text, flavor) {
      try {
        this._copyToSystemClipboard(text, flavor)
        return true
      } catch (e) {
        return false
      }
    },
    
    /**
     * TODO update to 2018!
     * Copy text to the clipboard of the underlying operating system
     * and throws an error if not successful
     * sources: http://www.krikkit.net/howto_javascript_copy_clipboard.html
     *          http://www.xulplanet.com/tutorials/xultu/clipboard.html
     *          http://www.codebase.nl/index.php/command/viewcode/id/174
     *
     * works only in Mozilla and Internet Explorer
     * In Mozilla, add this line to your prefs.js file in your Mozilla user profile directory
     *    user_pref("signed.applets.codebase_principal_support", true);
     * or change the setting from within the browser with calling the "about:config" page
     **/
    _copyToSystemClipboard: function (text, flavor) {
      if (!flavor) {
      //   // default
      //   flavor = 'text/unicode'
      // }
      //
      // if (window.clipboardData) {
      //   // IE
      //   window.clipboardData.setData('Text', text)
      // }
      // else if (window.netscape) {
      //   // Mozilla, Firefox etc.
      //   try {
      //     netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect')
      //   }
      //   catch (e) {
      //     throw new Error(
      //       'Because of tight security settings in Mozilla / Firefox you cannot copy ' +
      //       'to the system clipboard at the moment. Please open the \'about:config\' page ' +
      //       'in your browser and change the preference \'signed.applets.codebase_principal_support\' to \'true\'.'
      //     )
      //   }
      //   // we could successfully enable the privilege
      //   let clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard)
      //   if (!clip) return
      //   let trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable)
      //   if (!trans) return
      //   trans.addDataFlavor(flavor)
      //   let len = {}
      //   let str = Components.classes['@mozilla.org/supports-string;1'].createInstance(Components.interfaces.nsISupportsString)
      //   let copytext = text
      //   str.data = copytext
      //   trans.setTransferData(flavor, str, copytext.length * 2)
      //   let clipid = Components.interfaces.nsIClipboard
      //   if (!clip) return false
      //   clip.setData(trans, null, clipid.kGlobalClipboard)
      //   return true
      }
      else {
        throw new Error('Your browser does not support copying to the clipboard!')
      }
    }
  },
  
  /*
  *****************************************************************************
     DESTRUCTOR
  *****************************************************************************
  */
  
  destruct: function () {
    this._disposeMap('__data', '__actions')
  }
});
