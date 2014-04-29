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

/**
 * A mixin that provides a "Loading..." popup over a widget that is 
 * just requesting data from the server
 * @asset(qcl/ajax-loader.gif)
 */
qx.Mixin.define("qcl.ui.MLoadingPopup",
{
  
  members:
  {
    __popup : null,
    __popupAtom : null,
    __target : null,
    
     /**
      * Creates the popup
      * @param options {Map}
      */
     createPopup : function( options )
     {
        if ( options === undefined )
        {
          options = {};
        }
        else if( ! qx.lang.Type.isObject( options ) )
        {
          this.error("Invalid argument.");
        }
        
        this.__popup = new qx.ui.popup.Popup(new qx.ui.layout.Canvas()).set({
          decorator: "group",
          minWidth  : 100,
          minHeight : 30,
          padding   : 10
        });
        this.__popupAtom = new qx.ui.basic.Atom().set({
          label : options.label !== undefined ? options.label : "Loading ...",
          icon  : options.icon  !== undefined ? options.icon : "qcl/ajax-loader.gif",
          rich  : options.rich !== undefined ? options.rich :true,
          iconPosition  : options.iconPosition !== undefined ? options.iconPosition : "left",
          show  : options.show !== undefined ? options.show :  "both",
          height : options.height || null,
          width : options.width || null
        });
        this.__popup.add( this.__popupAtom );
        this.__popup.addListener("appear", this._centerPopup, this);           
     },
     
     /**
      * Centers the popup
      */
     _centerPopup :function()
     {
        var bounds = this.__popup.getBounds();
        if ( this.__target && ( "left" in this.__target.getLayoutProperties() ) )
        {
          var l = this.__target.getLayoutProperties();
          this.__popup.placeToPoint({
            left: Math.round( l.left + ( l.width / 2) - ( bounds.width / 2) ),
            top : Math.round( l.top + ( l.height / 2 ) - ( bounds.height / 2) )
          });          
        }
        else
        {
          this.__popup.set({
            marginTop: Math.round( ( qx.bom.Document.getHeight() -bounds.height ) / 2),
            marginLeft : Math.round( ( qx.bom.Document.getWidth() -bounds.width) / 2)
          });          
        }
     },
     
     /**
      * Shows the popup centered over the widget
      * @param label {String}
      * @param target {qx.ui.core.Widget} Optional target widet. If not given,
      * use the including widget.
      */
     showPopup : function( label, target )
     {
       if ( label )
       {
          this.__popupAtom.setLabel( label );
       }
       this.__target = target;
       this.__popup.show();       
     },
     
     /**
      * Hides the widget
      */
     hidePopup : function()
     {
       this.__popup.hide();
     }
   },

   /**
    * Destructor
    */
  destruct : function() {
    this._disposeObjects("__popup","this.__popupAtom");
  }   
});