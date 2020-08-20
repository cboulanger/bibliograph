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
 * Z39.50 Plugin: Application logic
 * @property {bibliograph.ConfigManager} configManager
 */
qx.Class.define("bibliograph.plugins.z3950.View", {
  extend: bibliograph.ui.abstract.ImportWindowView,
  
  /**
   * Constructor
   */
  construct: function () {
    this.base(arguments);
    this.setModuleName("z3950");
    this.setLayout(new qx.ui.layout.VBox(5));
    this.createPopup();
    this.add(this.getQxObject("toolbar"));
    this.add(this.getQxObject("listview"), {flex: 1});
    this.add(this.getQxObject("footer"));
    this._setupProgressWidget();
  },
  
  members:
  {
    
    /**
     * Starts the search
     * @override
     */
    startSearch: function () {
      this.base(arguments).startSearch(this.normalizeForSearch(this.getSearch()));
    },
    
    markForTranslation: function () {
      this.tr("Import from library catalog");
    },
    
    /**
     * from https://github.com/ikr/normalize-for-search/blob/master/src/normalize.js
     * MIT licence
     * @param s {String}
     * @return {String}
     */
    normalizeForSearch: function (s) {
      // ES6: @todo
      //let combining = /[\u0300-\u036F]/g;
      // return s.normalize('NFKD').replace(combining, ''));
      
      /**
       * @param c
       */
      function filter(c) {
        switch (c) {
          case "ä":
            return "ae";
          
          case "å":
            return "aa";
          
          case "á":
          case "à":
          case "ã":
          case "â":
            return "a";
          
          case "ç":
          case "č":
            return "c";
          
          case "é":
          case "ê":
          case "è":
            return "e";
          
          case "ï":
          case "í":
            return "i";
          
          case "ö":
            return "oe";
          
          case "ó":
          case "õ":
          case "ô":
            return "o";
          
          case "ś":
          case "š":
            return "s";
          
          case "ü":
            return "ue";
          
          case "ú":
            return "u";
          
          case "ß":
            return "ss";
          
          case "ё":
            return "е";
          
          default:
            return c;
        }
      }
      
      let normalized = "";
      let i;
      let l;
      s = s.toLowerCase();
      for (i = 0, l = s.length; i < l; i += 1) {
        normalized += filter(s.charAt(i));
      }
      return normalized;
    }
  }
});
