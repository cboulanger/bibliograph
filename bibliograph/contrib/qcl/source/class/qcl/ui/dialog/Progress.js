/* ************************************************************************

 qcl - the qooxdoo component library

 http://qooxdoo.org/contrib/project/qcl/

 Copyright:
 2007-2014 Christian Boulanger

 License:
 LGPL: http://www.gnu.org/licenses/lgpl.html
 EPL: http://www.eclipse.org/org/documents/epl-v10.php
 See the LICENSE file in the project's top-level directory for details.

 Authors:
 *  Christian Boulanger (cboulanger)

 ************************************************************************ */
/*global qcl qx dialog*/

/**
 * Extends the dialog widget set to provide server-generated dialogs and popups
 */
qx.Class.define("qcl.ui.dialog.Progress",
{
  extend : dialog.Dialog,


  /*
   *****************************************************************************
   PROPERTIES
   *****************************************************************************
   */
  properties :
  {
    /**
     * Whether the dialog is visible. This allows to turn the widget on or
     * off with a boolean value rather than with the string values required
     * by the "visibility" property.
     * (will be moved into the dialog.Dialog)
     */
    visible :
    {
      check    : "Boolean",
      init     : true,
      nullable : false,
      event    : "changeVisible",
      apply    : "_applyVisible"
    },

    /**
     * The percentage of the progress, 0-100
     */
    progress :
    {
      check    : function(value){ return value >= 0 && value <= 100 },
      init     : null,
      nullable : true,
      event    : "changeProgress"
    },

    /**
     * The content of the log
     */
    logContent :
    {
      check    : "String",
      init     : "",
      event    : "changeLogContent"
    },

    /**
     * New text that should be written to the log
     */
    newLogText :
    {
      check    : "String",
      nullable : false,
      event    : "changeNewLogText",
      apply    : "_applyNewLogText"
    },

    /**
     * Whether or not the progress bar is visible
     * (default: true)
     */
    showProgressBar :
    {
      check    : "Boolean",
      nullable : false,
      init     : true,
      event    : "changeShowProgressBar"
    },

    /**
     * Whether or not the log is visible
     * (default: false)
     */
    showLog :
    {
      check    : "Boolean",
      nullable : false,
      init     : false,
      event    : "changeShowLog"
    },

    /**
     * The text of the OK button. If null, hide the button.
     * (default: null)
     */
    okButtonText  :
    {
      check    : "String",
      nullable : true,
      init     : null,
      event    : "changeOkButtonText",
      apply    : "_applyOkButtonText"
    }
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
     APPLY METHODS
     ---------------------------------------------------------------------------
     */

    /**
     * Will be moved into dialog.Dialog
     */
    _applyVisible : function(value,old)
    {
      value ? this.show() : this.hide();
    },


    /**
     * Adds new text to the log
     */
    _applyNewLogText : function(value,old)
    {
      if( value )
      {
        var content = this.getLogContent();
        this.setLogContent(
            content ? content + "\n" + value : value
        );
      }
    },

    /**
     * Apply the OK Button text
     */
    _applyOkButtonText : function(value, old)
    {
      if (value === null)
      {
        this._okButton.setVisibility("excluded");
        return;
      }
      this._okButton.setLabel(value);
      this._okButton.show();
    },

    /*
     ---------------------------------------------------------------------------
     WIDGET LAYOUT
     ---------------------------------------------------------------------------
     */

    _progressBar : null,
    _logView : null,

    /**
     * Create the content of the dialog.
     * Extending classes must implement this method.
     */
    _createWidgetContent : function()
    {
      /*
       * groupbox
       */
      var container = new qx.ui.groupbox.GroupBox().set({
        contentPadding: [16, 16, 16, 16]
      });
      container.setLayout( new qx.ui.layout.VBox(10) );
      this.add( container );

      /*
       * Add progress bar with a cancel button
       */
      var hbox = new qx.ui.container.Composite();
      hbox.set({
        layout : new qx.ui.layout.HBox(10),
        height : 30
      });
      container.add(hbox);

      this._progressBar = new qx.ui.indicator.ProgressBar();
      this._progressBar.set({
        maxHeight : 20,
        alignY : "middle"
      });
      this.bind("progress",this._progressBar,"value");
      hbox.add(this._progressBar, {flex:1});

      var cancelButton = this._createCancelButton();
      cancelButton.set({
        label  : null,
        height : 20,
        icon   : "bibliograph/icon/16/cancel.png",
        alignY : "middle",
        visibility : "excluded"
      });
      hbox.add(cancelButton);
      this.bind("showLog",hbox, "visibility",{
        converter : function(v){ return v ? "visible" : "excluded"; }
      });

      /*
       * Add message label
       */
      this._message = new qx.ui.basic.Label();
      this._message.set({
        rich : true,
        textAlign: "center"
      });
      container.add(this._message);

      /*
       * Log area
       */
      this._logView = new qx.ui.form.TextArea();
      this._logView.set({
        visibility : "excluded",
        height: 200
      });
      container.add(this._logView, {flex:1});
      this.bind("showLog",this._logView, "visibility",{
        converter : function(v){ return v ? "visible" : "excluded"; }
      });
      this.bind("logContent", this._logView, "value");
      this._logView.bind("value",this, "logContent");

      /*
       * Ok Button
       */
      var okButton = this._createOkButton();
      okButton.set({
        visibility : "excluded",
        enabled    : false,
        alignX     : "center",
        icon       : null
      });
      this._progressBar.addListener("complete",function(){
        okButton.setEnabled(true);
      },this);
      container.add(okButton,{});
    }
  }
});

//      var progressWidget = new qcl.ui.dialog.Progress({
//        showLog : true,
//        okButtonText : "Continue"
//      });
//      progressWidget.show();
//
//      var counter = 0;
//      (function textProgress()
//      {
//        progressWidget.set({
//          progress : counter,
//          message  : counter + "% completed"
//        });
//
//        if ( counter % 10 == 0 )
//        {
//          progressWidget.setNewLogText( counter + "% completed" );
//        }
//
//        if( counter++ == 100 )return;
//        qx.lang.Function.delay(textProgress,100,this);
//      })();