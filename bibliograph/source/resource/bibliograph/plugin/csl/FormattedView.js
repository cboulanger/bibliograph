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
qx.Class.define("bibliograph.plugin.csl.FormattedView",
{
  extend : qx.ui.container.Composite,
  include : [qcl.ui.MLoadingPopup],

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
    this.createPopup();
    this.loadStyles();
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
    loadHtml : function()
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
      if (!ids.length)
      {
        this.viewPane.setHtml("");
        return;
      }

      /*
       * don't reload what we already loaded
       */
      if (this.__lastIds && qx.lang.Array.equals(ids, this.__lastIds)) {
        return;
      }
      this.__lastIds = ids;

      /*
       * show with a small timeout
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
          if (ids.length != selIds.length || ids[0] != selIds[0]) {
            return;
          }
          this.showPopup(this.tr("Generate formatted citations ..."));
          app.getRpcManager().execute("bibliograph.plugin.csl.Service", "render", [app.getDatasource(), ids, styleId], function(data)
          {
            this.viewPane.setHtml(data.html);
            this.hidePopup();
          }, this);
        }, this, 500);
      }
    },
    loadFolder : function()
    {
      this.showPopup(this.tr("Generate formatted citations ..."));
      var app = this.getApplication();
      var configManager = this.getApplication().getConfigManager();
      var styleId = configManager.getKey("csl.style.default");
      var folderId = app.getFolderId();
      if (!folderId)return;

      app.getRpcManager().execute("bibliograph.plugin.csl.Service", "renderFolder", [app.getDatasource(), folderId, styleId], function(data)
      {
        this.viewPane.setHtml(data.html);
        this.hidePopup();
      }, this);
    },

    /**
     * Load the styles and populate menu
     */
    loadStyles : function()
    {
      if (this.styleRadioGroup)
      {
        this.styleMenu.removeAll();
        this.styleRadioGroup.dispose();
      }
      this.styleRadioGroup = new qx.ui.form.RadioGroup;
      var configManager = this.getApplication().getConfigManager();
      var defaultStyle = configManager.getKey("csl.style.default");
      this.styleRadioGroup.addListener("changeSelection", function(e)
      {
        var sel = e.getData();
        if (sel.length)
        {
          var styleId = sel[0].getUserData("styleId");
          configManager.setKey("csl.style.default", styleId);
          this.__lastIds = null;
          this.loadHtml(styleId);
        }
      }, this);
      this.getApplication().getRpcManager().execute("bibliograph.plugin.csl.Service", "getStyleData", [], function(styleData)
      {
        if (!qx.lang.Type.isArray(styleData)) {
          dialog.alert("Invalid style data.");
        }
        styleData.forEach(function(style)
        {
          var rb = new qx.ui.menu.RadioButton(style.title);
          rb.setUserData("styleId", style.id);
          this.styleMenu.add(rb);
          this.styleRadioGroup.add(rb);
          if (style.id == defaultStyle) {
            this.styleRadioGroup.setSelection([rb]);
          }
        }, this);
      }, this);
    }
  }
});
