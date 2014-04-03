/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2010 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

/**
 * A manager for native child windows that share the main application's object
 * tree. Not yet functional, depends on the resolution
 * of bug <a href="http://bugzilla.qooxdoo.org/show_bug.cgi?id=3086">3096</a>.
 */
qx.Class.define("qcl.application.NativeWindowManager",
{
  extend : qx.core.Object,
  
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  properties :
  {
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
       WIDGETS
    ---------------------------------------------------------------------------
    */

    
    /* 
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */       
    /**
     * Child windows opened by this application
     */
    _windows : {},
    
    /* 
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */   
    
    /**
     * Sets the window title/caption. If the window is connected to a 
     * menu button, set the label of this button.
     * @param title {String}
     * @return
     */
    setWindowTitle : function( title )
    {
      document.title = title;
      if ( window.menuButton )
      {
        window.menuButton.setLabel(title);
      }
      
    },    
    
    /** 
     * Start an application in a new window or bring the
     * window to the front if it has already been opened.
     *
     * @param application {String} class name of application
     * @param state {Map} application state
     * @param width {Int} Width of window
     * @param height {Int} Height of window     
     * @return {qx.bom.Window} 
     */
    startApplication : function( application, state, width, height )
    {
      /*
       * add session id and access mode to the state
       */
      state.parentSessionId = qx.core.Init.getApplication().getSessionManager().getSessionId();
      state.access = "continue";
      
      /*
       * convert into string
       */
      var stateArr = [];
      for ( var key in state )
      {
        stateArr.push( key + "=" + encodeURIComponent( state[key] ) )
      }
      var stateStr = "#" + stateArr.join("&");
      var w = this._windows[stateStr];
      if ( w instanceof qx.bom.Window ) 
      {
        w.focus();
        return w;
      }
      
      /*
       * open new window
       */
      w = new qx.bom.Window("?application=" + application + stateStr );      
      w.setAllowScrollbars(false);
      if (width && height) 
      {
        w.setDimension(width, height);
      }
      else
      {
         w.setDimension(800, 400);
      }
      w.open();

      /*
       * check if window has been blocked
       */
      if (! w.isOpen() )
      {
        alert("Cannot open popup window. Please disable your popup blocker.");
        return null;
      }

      /*
       * delete reference on close
       */
      w.addEventListener("close", function() {
        delete this._windows[stateStr];
        delete w;
      }, this);

      /*
       * save window in registry
       */
      this._windows[stateStr] = w;
      
      return w;
    },

    
   /** 
    * Starts an application in a new window and creates a menu button connected with 
    * this window. When the button is clicked, the window gets the focus. Returns a 
    * qx.ui.menu.Button widget with the connected window reference attached as the 
    * "window" property
    *
    * @param application {String} class name of application
    * @param state {Map} application state
    * @param width {Int} Width of window
    * @param height {Int} Height of window
    * @param label {String} Label of the menu button connected to the window
    * @return {qx.ui.menu.Button} 
    */
   startApplicationWithMenuButton : function( application, state, width, height, label ) 
   {
     /*
      * window
      */
     var win = this.startApplication( application, state, width, height );
     
     /*
      * menu button
      */
     var menuButton = new qx.ui.menu.Button( label );
     
     /*
      * attach reference to window as the "window" property
      * and vice versa
      */
     console.log(menuButton.window);
     menuButton.window = win;
     win._window.menuButton = menuButton;
     
     /*
      * when button is clicked, give the focus to the window
      */
     menuButton.addEventListener("execute", function() {
       win.focus();
     });
     
     /*
      * when the window is closed, delete the button
      */
     win.addEventListener("close", function() {
       menuButton.getParent().remove(menuButton);
       menuButton.dispose();
       menuButton.destroy();
       win.dispose();
       delete win;
     });

     return menuButton;
   }
  }
});