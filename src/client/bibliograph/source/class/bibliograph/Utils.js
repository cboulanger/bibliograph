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
  extend : qx.core.Object,
  statics :
  {
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