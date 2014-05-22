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

/**
 *
 */
qx.Class.define("bibliograph.ui.item.TableView",
{
  extend : qx.ui.container.Composite,

  /*
    *****************************************************************************
       PROPERTIES
    *****************************************************************************
    */
  properties : {

  },

  /*
    *****************************************************************************
        CONSTRUCTOR
    *****************************************************************************
    */
  construct : function()
  {
    this.base(arguments);
  },

  /*
    *****************************************************************************
        MEMBERS
    *****************************************************************************
    */
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
    _on_appear : function()
    {
      var app = this.getApplication();
      if (app.getDatasource() && app.getModelId()) {
        app.getRpcManager().execute("bibliograph.reference", "getHtml", [app.getDatasource(), app.getModelId()], function(data) {
          this.viewPane.setHtml(data.html);
        }, this);
      }
    },


    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * TODOC
     *
     * @return {void}
     */
    loadHtml : function()
    {
      var app = this.getApplication();
      var id = app.getModelId();
      if (!id)
      {
        this.viewPane.setHtml("");
        return;
      }
      if (this.isVisible() && app.getDatasource()) {
        qx.event.Timer.once(function() {
          if (id == app.getModelId())
          {
            app.showPopup(this.tr("Loading data..."));
            app.getRpcManager().execute("bibliograph.reference", "getHtml", [app.getDatasource(), id], function(data)
            {
              this.viewPane.setHtml(data.html);
              app.hidePopup();
            }, this);
          }
        }, this, 500);
      }
    }
  }
});
