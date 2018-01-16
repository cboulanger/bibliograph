/* ************************************************************************

  Bibliograph. The open source online bibliographic data manager

  http://www.bibliograph.org

  Copyright: 
    2018 Christian Boulanger

  License: 
    MIT license
    See the LICENSE file in the project's top-level directory for details.

  Authors: 
    Christian Boulanger (@cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * Utility methods as static properties of the class
 */
qx.Class.define("bibliograph.Utils",
{
  type : "static",
  statics :
  {
    /**
     * Callback function that takes the username, password and
     * another callback function as parameters.
     * The callback is called with (err, data)
     *
     * @param username {String}
     * @param password {String}
     * @param callback {Function}
     * @return {Promise<void>}
     */
    checkLogin : async function(username, password, callback)
    {
      var app = qx.core.Init.getApplication();
      app.createPopup();
      app.showPopup(app.tr("Authenticating ..."));
      let result = await app.getAccessManager().authenticate(username, password);
      app.hidePopup();
      callback( result.error, result );
    },    

    /**
     * Helper function for converters in list databinding. If a selected element
     * exist, returns its model value, otherwise return null
     *
     * @param selection {Array} TODOC
     * @return {String | null} TODOC
     */
    getSelectionValue : function(selection) {
      return selection.length ? selection[0].getModel().getValue() : null;
    },

    /**
     * Given a value, return the list element that has the
     * matching model value wrapped in an array. If nothing
     * has been found, return an empty array
     *
     * @param value {String} TODOC
     * @return {Array} TODOC
     */
    getModelValueListElement : function(value)
    {
      for (var i = 0, c = this.getChildren(); i < c.length; i++) {
        if (c[i].getModel().getValue() == value) {
          return [c[i]];
        }
      }

      // console.warn( "Did not find " + value );
      return [];
    },

    bool2visibility : function(state)
    {
      return state ? 'visible' : 'excluded';
    },
    
    utf8_encode : function ( string )
    {
      return unescape( encodeURIComponent( string ) );
    },

    utf8_decode : function( string )
    {
      return decodeURIComponent( escape( string ) );
    },
    
    html_entity_decode : function(str) 
    {
      var ta=document.createElement("textarea");
      ta.innerHTML=str.replace(/</g,"&lt;").replace(/>/g,"&gt;");
      return ta.value;
    },
    
    strip_tags : function (html)
    {
      return html.replace(/(<([^>]+)>)/ig,"");
    },
    
    br2nl : function( html )
    {
      return html.replace(/<br[\s]*\/?>/ig,"\n");
    }
  },  

});