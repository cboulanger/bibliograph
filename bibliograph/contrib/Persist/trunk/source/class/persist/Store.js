/* ************************************************************************

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

/**
 * This is a thin wrapper around the persistjs library
 * See http://pablotron.org/software/persist-js/
 * @ignore(Persist.Store)
 * @asset(persist/*)
 */
qx.Class.define("persist.Store",
{
  extend : qx.core.Object,
  
  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  /**
   * Contructor. Creates a new browser store
   * @param name {String} Identifying name of the store. 
   * @param expires {Integer|undefined} Optional expiration in days, defaults
   *   to one year
   * @param domain {String|undefined} Optional domain, defaults to current 
   *   domain
   * @param path {String|undefined} Optional path, defaults to '/'
   */
  construct : function( name, expires, domain, path )
  {
    this.base(arguments);
    this.__store = new Persist.Store(name, {
      'expires' : expires,
      'domain'  : domain,
      'path'    : path
    });
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
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */       
    __store : null,
    
    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */
    
    /**
     * Fetches the value stored under the given key and calls the callback
     * function with the value. The callback is necessary for cross-browser
     * compatibility. It is called with the following signature:
     * (success {Boolean}, value {String}). success will contain true if the
     * value could be retrieved and false if not. 
     * 
     * @param key {String}
     * @param callback {Function} The callback function
     * @param scope {Object} The scope of the callback
     */
    load : function( key, callback, scope )
    {
      this.__store.get( key, callback, scope );
    },
    
    /**
     * Saves the given value to the store under the given key
     * @param key {String}
     * @param value {String}
     * @param callback {Function} The callback function
     * @param scope {Object} The scope of the callback 
     */
    save : function ( key, value, callback, scope )
    {
      this.__store.set( key, value, callback, scope );
    },
    
    /**
     * Removes the given key in the store
     * @param key {String}
     * @param callback {Function} The callback function
     * @param scope {Object} The scope of the callback 
     */
    remove : function ( key, callback, scope )
    {
      this.__store.set( key, callback, scope );
    }    
  }
});