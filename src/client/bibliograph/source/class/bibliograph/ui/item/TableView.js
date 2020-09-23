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

/**
 *
 */
qx.Class.define("bibliograph.ui.item.TableView",
{
  extend : qx.ui.container.Composite,
  members :
  {
    /*
    ---------------------------------------------------------------------------
       EVENT HANDLERS
    ---------------------------------------------------------------------------
    */

    /**
     * TODOC
     *
     * @return {void}
     */
    _on_appear : function() {
      var app = this.getApplication();
      if (app.getDatasource() && app.getModelId()) {
        this._load(app.getDatasource(), app.getModelId());
      }
    },

    /**
     * loads the HTML from the server
     *
     * @param datasource
     * @param id
     */
    async _load(datasource, id) {
      this.setEnabled(false);
      this.viewPane.setHtml("");
      let service = await bibliograph.store.Datasources.getServiceFor("reference");
      let data = await this.getApplication()
        .getRpcClient(service)
        .request("item-html", [datasource, id]);
      this.viewPane.setHtml(qx.lang.Type.isObject(data) ? data.html : "");
      this.setEnabled(true);
    },

    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */

    loadHtml : function() {
      var app = this.getApplication();
      var id = app.getModelId();
      if (!id) {
        this.viewPane.setHtml("");
        return;
      }
      if (this.isVisible() && app.getDatasource()) {
        qx.event.Timer.once(function() {
          if (id === app.getModelId()) {
            this._load(app.getDatasource(), id);
          }
        }, this, 500);
      }
    }
  }
});
