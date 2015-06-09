/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

/*global qx qcl z3950 dialog*/

/**
 * Z39.50 Plugin: Application logic
 */
qx.Class.define("z3950.ImportWindow",
{
  extend : qx.ui.window.Window,
  include : [qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  /**
   * Constructor
   */
  construct : function()
  {
    this.base(arguments);
    this.createPopup();
    qx.lang.Function.delay(function(){
      this.listView.addListenerOnce("tableReady", function() {
        var controller = this.listView.getController();
        function enableButtons (){
          this.importButton.setEnabled(true);
          this.searchButton.setEnabled(true);
          this.listView.setEnabled(true);
          this.hidePopup();
        }
        controller.addListener("blockLoaded", enableButtons, this);
        controller.addListener("statusMessage", function(e){
          this.showPopup(e.getData());
          qx.lang.Function.delay( enableButtons, 1000, this);
        }, this);
      }, this);
    },100,this);
  },

  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  members :
  {
    listView : null,
    datasourceSelectBox : null,
    searchBox : null,
    searchButton : null,
    statusTextLabel : null,

    /**
     * Called when the user presses a key in the search box
     * @param e {qx.event.type.Data}
     */
    _on_keypress : function(e) {
      if (e.getKeyIdentifier() == "Enter")
      {
        this.startSearch();
      }
    },

    /**
     * Starts the search
     */
    startSearch : function()
    {
      var ds = this.datasourceSelectBox.getSelection().getItem(0).getValue();
      var lv = this.listView;

      // update the UI
      lv.clearTable();
      this.importButton.setEnabled(false);
      this.searchButton.setEnabled(false);
      lv.setEnabled(false);

      // this triggers the search
      lv.setDatasource(ds);
      lv.setQuery(null);
      lv.setQuery( this.normalizeForSearch ( this.searchBox.getValue() ) );
    },

    /**
     * Imports the selected references
     */
    importSelected : function()
    {
      var app = this.getApplication();

      /*
       * ids to import
       */
      var ids = this.listView.getSelectedIds();
      if (!ids.length)
      {
        dialog.Dialog.alert(this.tr("You have to select one or more reference to import."));
        return false;
      }

      /*
       * target folder
       */
      var targetFolderId = app.getFolderId();
      if (!targetFolderId)
      {
        dialog.Dialog.alert(this.tr("Please select a folder first."));
        return false;
      }
      var treeView = app.getWidgetById("mainFolderTree");
      var nodeId = treeView.getController().getClientNodeId(targetFolderId);
      var node = treeView.getTree().getDataModel().getData()[nodeId];
      if (!node)
      {
        dialog.Dialog.alert(this.tr("Cannot determine selected folder. Please reload the folders."));
        return false;
      }
      if (node.data.type != "folder")
      {
        dialog.Dialog.alert(this.tr("Invalid target folder. You can only import into normal folders."));
        return false;
      }

      /*
       * send to server
       */
      var sourceDatasource = this.datasourceSelectBox.getSelection().toArray()[0].getValue();
      var targetDatasource = app.getDatasource();
      this.showPopup(this.tr("Importing references..."));
      this.getApplication().getRpcManager().execute("z3950.Service", "importReferences", [sourceDatasource, ids, targetDatasource, targetFolderId], function()
      {
        this.importButton.setEnabled(true);
        this.hidePopup();
      }, this);
    },
    
    markForTranslation : function() {
      this.tr("Import from library catalog");
    },

    /**
     * from https://github.com/ikr/normalize-for-search/blob/master/src/normalize.js
     * MIT licence
     * @param s {String}
     * @return {String}
     */
    normalizeForSearch : function (s) {

      // ES6:
      //var combining = /[\u0300-\u036F]/g;
      // return s.normalize('NFKD').replace(combining, ''));

      function filter(c) {
        switch (c) {
          case 'ä':
            return 'ae';

          case 'å':
            return 'aa';

          case 'á':
          case 'à':
          case 'ã':
          case 'â':
            return 'a';

          case 'ç':
          case 'č':
            return 'c';

          case 'é':
          case 'ê':
          case 'è':
            return 'e';

          case 'ï':
          case 'í':
            return 'i';

          case 'ö':
            return 'oe';

          case 'ó':
          case 'õ':
          case 'ô':
            return 'o';

          case 'ś':
          case 'š':
            return 's';

          case 'ü':
            return 'ue';

          case 'ú':
            return 'u';

          case 'ß':
            return 'ss';

          case 'ё':
            return 'е';

          default:
            return c;
        }
      }

      var normalized = '', i, l;
      s = s.toLowerCase();
      for (i = 0, l = s.length; i < l; i = i + 1) {
        normalized = normalized + filter(s.charAt(i));
      }
      return normalized;
    }
  }
});
