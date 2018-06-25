/* ************************************************************************

   Copyright: 2018 Christian Boulanger

   License: MIT license

   Authors: Christian Boulanger (cboulanger) info@bibliograph.org

************************************************************************ */

/* global top */

/**
 * This class wraps qx.ui.window.Window and a native popup window to provide a
 * unified API
 */
qx.Class.define('qcl.ui.window.Wrapper',
{
  extend: qx.core.Object,
  construct: function(native, width, height){
    this.base(arguments);
  },
  members:
  {
  
  }
});