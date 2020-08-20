/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2020 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

qx.Class.define("bibliograph.plugins.webservices.View",
{
  extend: bibliograph.ui.abstract.ImportWindowView,
  include: [qcl.ui.MLoadingPopup],
  properties : {
    /**
     * Whether to auto-import the first/best search result. This will also
     * auto-submit recognizable identifiers such as ISBNs or DOIs
     */
    autoimport : {
      check: "Boolean",
      init: false,
      event: "changeAutomimport"
    }
  },
  /**
   * Constructor
   */
  construct: function () {
    this.base(arguments);
    this.setModuleName("webservices");
    this.setLayout(new qx.ui.layout.VBox(5));
    this.createPopup();
    this.add(this.getQxObject("toolbar"));
    this.add(this.getQxObject("autoimport"));
    this.add(this.getQxObject("listview"), {flex: 1});
    this.add(this.getQxObject("footer"));
    this._setupProgressWidget();
  },
  members:
  {
    /**
     * @override
     * @param {String} id
     * @return {qx.ui.form.CheckBox|var}
     * @private
     */
    _createQxObjectImpl(id) {
      let control;
      switch (id) {
        case "autoimport":
          control = new qx.ui.form.CheckBox(this.tr("Auto-import best result"));
          control.bind("value", this, "autoimport");
          this.bind("autoimport", control, "value");
          this._autoimport = control;
          break;
      }
      return control || this.base(arguments, id);
    },
  
    /**
     * @override
     */
    _on_tableReady () {
      this.base(arguments);
      this._listView.getController().addListener("blockLoaded", () => {
        console.warn("Block loaded!");
        if (this.getAutoimport() && this.getSearch()) {
          this._selectFirstRow();
          this.importSelected();
          this.setSearch(null);
          this._searchBox.focus();
        }
      });
    },
  
    /**
     * Called when the user presses a key in the search box
     * @override
     * @param e {qx.event.type.Data}
     */
    _onKeypress: function (e) {
      if (e.getKeyIdentifier() === "Enter") {
        this.startSearch();
      }
      if (this.getAutoimport()) {
        let searchText = this.getSearch();
        // auto-submit ISBNs
        if (searchText && searchText.length > 12 && searchText.replace(/[^0-9xX]/g, "").length === 13 && searchText.substr(0, 3) === "978") {
          this.startSearch();
        }
      }
    }
  }
});
