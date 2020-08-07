/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2015 Christian Boulanger
  
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
qx.Mixin.define("qcl.ui.MLoadingPopup", {
  members: {
    __popup: null,
    __popupAtom: null,
    __target: null,

    /**
      * Creates the popup
      * @param options {Map}
      */
    createPopup: function(options) {
      if (options === undefined) {
        options = {};
      }
      qx.core.Assert.assertObject(options);
      if (this.__popup instanceof qx.ui.popup.Popup) {
        return;
      }
      this.__popup = new qx.ui.popup.Popup(new qx.ui.layout.Canvas()).set({
        decorator: "group",
        minWidth: 100,
        minHeight: 30,
        padding: 10
      });
      this.__popupAtom = new qx.ui.basic.Atom().set({
        label: options.label !== undefined ? options.label : "Loading ...",
        icon: options.icon !== undefined ? options.icon : "bibliograph/ajax-loader.gif",
        rich: options.rich !== undefined ? options.rich : true,
        iconPosition:
          options.iconPosition !== undefined ? options.iconPosition : "left",
        show: options.show !== undefined ? options.show : "both",
        height: options.height || null,
        width: options.width || null
      });
      this.__popup.add(this.__popupAtom);
      this.__popup.addListener("appear", this._centerPopup, this);
    },

    /**
      * Centers the popup
      */
    _centerPopup: function() {
      var bounds = this.__popup.getBounds();
      if (!bounds) {
        // need one more tick to appear
        qx.event.Timer.once(this._centerPopup, this, 0);
        return;
      }
      if (this.__target) {
        // center popup inside target
        var layoutProps = this.__target.getLayoutProperties();
        if (!layoutProps) {
          // needs one more tick to appear
          qx.event.Timer.once(this._centerPopup, this, 0);
        }
        this.__popup.placeToPoint({
          left: Math.round(layoutProps.left + layoutProps.width / 2 - bounds.width / 2),
          top: Math.round(layoutProps.top + layoutProps.height / 2 - bounds.height / 2)
        });
        return;
      }
      // center popup inside viewport
      this.__popup.set({
        marginTop: Math.round(
          (qx.bom.Document.getHeight() - bounds.height) / 2
        ),
        marginLeft: Math.round(
          (qx.bom.Document.getWidth() - bounds.width) / 2
        )
      });
    },

    /**
      * Shows the popup centered over the widget
      * @param label {String}
      * @param target {qx.ui.core.Widget} Optional target widet. If not given,
      * use the including widget.
      */
    showPopup: function(label, target) {
      if (label) {
        this.__popupAtom.setLabel(label);
      }
      this.__target = target;
      this.__popup.show();
    },

    /**
      * Hides the widget
      */
    hidePopup: function() {
      this.__popup.hide();
    }
  },

  /**
    * Destructor
    */
  destruct: function() {
    this._disposeObjects("__popup", "this.__popupAtom");
  }
});
