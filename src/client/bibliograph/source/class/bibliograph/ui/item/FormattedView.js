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
 * Application logic
 */
qx.Class.define("bibliograph.ui.item.FormattedView",
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
    this.loadStyles();
    this.getApplication().addListener("changeSelectedIds", this.loadHtml, this);
    this.addListener("appear", this._on_appear, this);
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
       WIDGETS
    ---------------------------------------------------------------------------
    */
    menuBar : null,
    styleMenu : null,
    styleRadioGroup : null,

    /*
    ---------------------------------------------------------------------------
       EVENT HANDLERS
    ---------------------------------------------------------------------------
    */
    _on_appear : function()
    {
      this.__lastIds = null;
      this.loadHtml();
    },

    /*
    ---------------------------------------------------------------------------
       INTERNAL METHODS
    ---------------------------------------------------------------------------
    */
    markForTranslation : function() {
      this.tr("Formatted View");
    },

    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * Load the html code of the formatted reference which are currently
     * selected in the listview
     */
    loadHtml : function(value)
    {
      var app = this.getApplication();
      var configManager = this.getApplication().getConfigManager();
      var styleId = configManager.getKey("csl.style.default");
      var ids = app.getSelectedIds().map(function(value) {
        return parseInt(value);
      }, this);

      /*
       * clear if no selection
       */
      if ( ids.length == 0 )
      {
        if( app.getModelId() )
        {
          ids = [app.getModelId()];
        }
        else
        {
          this.viewPane.setHtml("");
          return;
        }
      }

      /*
       * don't reload what we already loaded
       */
      if (this.__lastIds && qx.lang.Array.equals(ids, this.__lastIds)) {
        return;
      }
      this.__lastIds = ids;

      /*
       * show with a small timeout to avoid too many requests
       */
      if (this.isVisible() && app.getDatasource()) {
        qx.event.Timer.once(function()
        {
          /*
           * check if selection has not changed meanwhile
           * and if yes,
           * don't send request to server. We check for
           * selection length and the first element of the
           * selection.
           */
          var selIds = app.getSelectedIds().map(function(value) {
            return parseInt(value);
          }, this);
          if( selIds.length==0 && app.getModelId() ) {
            selIds = [app.getModelId()];
          }

          if (ids.length != selIds.length || ids[0] != selIds[0]) {
            return;
          }

          this.setEnabled(false);
          this.viewPane.setHtml("");
          app.getRpcClient("citation").send( "render-items", [app.getDatasource(), ids, styleId])
          .then((data)=>{
            this.viewPane.setHtml(data && data.html ? data.html : "");
            this.setEnabled(true);
          })
          .catch((err)=>{
            this.warn(err);
          });
        }, this, 500);
      }
    },

    /**
     * Load the formatted text for the whole folder
     */
    loadFolder : function()
    {
      var app      = this.getApplication();
      var confMgr  = app.getConfigManager();
      var styleId  = confMgr.getKey("csl.style.default");
      var folderId = app.getFolderId();
      var query    = app.getQuery();
      
      if ( folderId )
      {
        this.viewPane.setHtml(this.tr("Loading formatted citations..."));
        this.setEnabled(false);
        app.getRpcClient("citation").send( "render-folder", [app.getDatasource(), folderId, styleId])
        .then((data)=>{
          this.viewPane.setHtml(data && data.html ? data.html : "");
          this.setEnabled(true);
        })
        .catch((err)=>{
          this.warn(err);
        });
      }
      else if ( query )
      {
        this.viewPane.setHtml(this.tr("Loading formatted citations..."));
        this.setEnabled(false);
        app.getRpcClient("citation").send( "render-query", [app.getDatasource(), query, styleId])
        .then((data)=>{
          this.viewPane.setHtml(data && data.html ? data.html : "");
          this.setEnabled(true);
        })
        .catch((err)=>{
          this.warn(err);
        });
      }
    },

    /**
     * Load the styles and populate menu
     */
    loadStyles : function()
    {
      console.warn("Not loading styles yes");
      return;
      if (this.styleRadioGroup)
      {
        this.styleMenu.removeAll();
        this.styleRadioGroup.dispose();
      }
      this.styleRadioGroup = new qx.ui.form.RadioGroup;
      var app = this.getApplication();
      var configManager = app.getConfigManager();
      configManager.addListener("ready", () => {
        var defaultStyle = configManager.getKey("csl.style.default");
        this.styleRadioGroup.addListener("changeSelection", (e) => {
          var sel = e.getData();
          if (sel.length) {
            var styleId = sel[0].getUserData("styleId");
            configManager.setKey("csl.style.default", styleId);
            this.__lastIds = null;
            this.loadHtml(styleId);
          }
        });
        app.getRpcClient("citation").send( "style-data" )
        .then( (styleData) => {
          if (!qx.lang.Type.isArray(styleData)) {
            this.warn("Invalid style data.");
            return;
          }
          styleData.forEach( (style) => {
            var rb = new qx.ui.menu.RadioButton(style.title);
            rb.setUserData("styleId", style.id);
            this.styleMenu.add(rb);
            this.styleRadioGroup.add(rb);
            if (style.id == defaultStyle) {
              this.styleRadioGroup.setSelection([rb]);
            }
          });
        })
        .catch((err)=>{
          this.warn(err);
        });
      });
    }
  }
});
