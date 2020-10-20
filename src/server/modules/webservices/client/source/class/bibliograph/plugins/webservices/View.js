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
      event: "changeAutomimport",
      apply: "_applyAutoimport"
    },
  
    /**
     * Whether to autostart the search when recognizing a valid pattern
     */
    autostart : {
      check: "Boolean",
      init: false,
      event: "changeAutostart"
    }
  },
  /**
   * Constructor
   */
  construct: function () {
    this.base(arguments);
    this.setModuleName("webservices");
    this.set({
      layout: new qx.ui.layout.VBox(5),
      allowStretchY: true
    });
    this.createPopup();
    this.add(this.getQxObject("toolbar"));
    this.add(this.getQxObject("toolbar2"));
    this.add(this.getQxObject("listview"), {flex: 1});
    this.add(this.getQxObject("footer"));
    this._setupProgressWidget();
    qx.lang.Function.delay(() => {
      this.getWindow().setAllowStretchY(true);
    }, 100, this);
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
        case "toolbar2":
          control = new qx.ui.container.Composite(new qx.ui.layout.HBox(5));
          control.add(this.getQxObject("autoimport"));
          control.add(this.getQxObject("autostart"));
          break;
        case "autoimport":
          control = new qx.ui.form.CheckBox(this.tr("Auto-import best result"));
          control.bind("value", this, "autoimport");
          this.bind("autoimport", control, "value");
          break;
        case "autostart":
          control = new qx.ui.form.CheckBox(this.tr("Autostart search"));
          control.bind("value", this, "autostart");
          this.bind("autostart", control, "value");
          break;
      }
      return control || this.base(arguments, id);
    },
  
    _applyAutoimport(value) {
      // let visibility = value ? "excluded" : "visible";
      // this.getQxObject("listview").setVisibility(visibility);
      // this.getQxObject("footer").setVisibility(visibility);
    },
  
    /**
     * @override
     */
    _on_tableReady () {
      this.base(arguments);
      this._listView.getController().addListener("blockLoaded", () => {
        if (this._selectFirstRow() && this.getAutoimport()) {
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
    _on_input: function (e) {
      if (this.getAutostart()) {
        let searchText = this._searchBox.getValue();
        if (this.__timer) {
          this.__timer.restart();
        } else {
          this.__timer = qx.event.Timer.once(() => {
            delete this.__timer;
            if (this._searchBox.getValue() === searchText) {
              this.startSearch();
            }
          }, null, 1000);
        }
      }
    },
    
    __checkForIdentifiers(text) {
      let id;
      switch (true) {
        case (text.substr(0, 3) === "978"):
          id = text.replace(/[^0-9xX]/g, "");
          break;
        case (text.match(bibliograph.plugins.webservices.Plugin.DOI_LONG_REGEX)):
          id = text;
          break;
      }
      if (id) {
        this.startSearch(id);
      }
    }
  }
});
