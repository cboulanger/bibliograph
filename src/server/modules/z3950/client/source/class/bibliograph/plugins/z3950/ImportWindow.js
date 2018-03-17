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

/*global qx qcl z3950 dialog*/

/**
 * Z39.50 Plugin: Application logic
 */
qx.Class.define("bibliograph.plugins.z3950.ImportWindow",
{
  extend: qx.ui.window.Window,
  include: [qcl.ui.MLoadingPopup],
  
  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */
  
  /**
   * Constructor
   */
  construct: function () {
    this.base(arguments);
    this.createPopup();
    qx.lang.Function.delay(() => {
      this.listView.addListenerOnce("tableReady", () => {
        let controller = this.listView.getController();
        let enableButtons = () => {
          this.importButton.setEnabled(true);
          this.searchButton.setEnabled(true);
          this.listView.setEnabled(true);
          this.hidePopup();
        };
        controller.addListener("blockLoaded", enableButtons);
        controller.addListener("statusMessage", (e) => {
          this.showPopup(e.getData());
          qx.lang.Function.delay(enableButtons, 1000, this);
        });
      });
    }, 100);
  },
  
  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  members:
  {
    listView: null,
    datasourceSelectBox: null,
    searchBox: null,
    searchButton: null,
    statusTextLabel: null,
    
    /**
     * Called when the user presses a key in the search box
     * @param e {qx.event.type.Data}
     */
    _on_keypress: function (e) {
      if (e.getKeyIdentifier() == "Enter") {
        this.startSearch();
      }
    },
    
    /**
     * Starts the search
     */
    startSearch: function () {
      let ds = this.datasourceSelectBox.getSelection().getItem(0).getValue();
      let lv = this.listView;
      
      lv.setDatasource(ds);
      let query = this.normalizeForSearch(this.searchBox.getValue());
      
      // update the UI
      lv.clearTable();
      //lv.setEnabled(false);
      this.importButton.setEnabled(false);
      //this.searchButton.setEnabled(false);
      
      // open the ServerProgress widget and initiate the remote search
      let z3950Progress = this.getApplication().getWidgetById("z3950Progress");
      z3950Progress.setMessage(this.tr("Searching..."));
      z3950Progress.start([ds, query]);
    },
    
    
    /**
     * Imports the selected references
     */
    importSelected: function () {
      let app = this.getApplication();
      
      // ids to import
      let ids = this.listView.getSelectedIds();
      if (!ids.length) {
        dialog.Dialog.alert(this.tr("You have to select one or more reference to import."));
        return false;
      }
      
      // target folder
      let targetFolderId = app.getFolderId();
      if (!targetFolderId) {
        dialog.Dialog.alert(this.tr("Please select a folder first."));
        return false;
      }
      let treeView = app.getWidgetById("bibliograph/mainFolderTree");
      let nodeId = treeView.getController().getClientNodeId(targetFolderId);
      let node = treeView.getTree().getDataModel().getData()[nodeId];
      if (!node) {
        dialog.Dialog.alert(this.tr("Cannot determine selected folder. Please reload the folders."));
        return false;
      }
      if (node.data.type !== "folder") {
        dialog.Dialog.alert(this.tr("Invalid target folder. You can only import into normal folders."));
        return false;
      }
      
      // send to server
      let sourceDatasource = this.datasourceSelectBox.getSelection().toArray()[0].getValue();
      let targetDatasource = app.getDatasource();
      this.showPopup(this.tr("Importing references..."));
      this.getApplication()
        .getRpcClient("z3950/table")
        .send("importReferences", [sourceDatasource, ids, targetDatasource, targetFolderId])
        .then(()=>{
          this.importButton.setEnabled(true);
          this.hidePopup();
        });
    },
    
    markForTranslation: function () {
      this.tr("Import from library catalog");
    },
    
    /**
     * from https://github.com/ikr/normalize-for-search/blob/master/src/normalize.js
     * MIT licence
     * @param s {String}
     * @return {String}
     */
    normalizeForSearch: function (s) {
      
      // ES6: @todo
      //let combining = /[\u0300-\u036F]/g;
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
      
      let normalized = '', i, l;
      s = s.toLowerCase();
      for (i = 0, l = s.length; i < l; i = i + 1) {
        normalized = normalized + filter(s.charAt(i));
      }
      return normalized;
    }
  }
});
