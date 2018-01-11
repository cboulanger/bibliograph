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
     * Christian Boulanger (cboulanger)

************************************************************************ */
/*global qx qcl */

/**
 * This manager is not to be used directly, but is exended by the Permission, Role and 
 * User Manager singletons.
 */
qx.Class.define("qcl.access.AbstractManager",
{
 
	extend : qx.core.Object,

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  construct : function()
  {
		this.base(arguments);
		this._index = {};
    this._objects = {};
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
    _index : null,
    _objects : null,
    _managedObjectClassName : null,
    _instance : null,
    
    /*
    ---------------------------------------------------------------------------
      USER API
    ---------------------------------------------------------------------------
    */    
    
    /**
     * Adds managed object
     *
     * @param vObject {var} TODOC
     * @return {void | Boolean} TODOC
     */
    add : function(vObject)
    {
			var hashCode = vObject.toHashCode(); 
      this._objects[hashCode] = vObject;
			this._index[vObject.getNamedId()] = hashCode;
    },

    /**
     * Removes managed object
     *
     * @param vObject {var} TODOC
     * @return {void | Boolean} TODOC
     */
    remove : function(vObject)
    {
			var hashCode = vObject.toHashCode();
      delete this._objects[hashCode];
			delete this._index[vObject.getNamedId()];
			return true;
    },
    
   /**
    * Checks if object is already managed
    * @param vObject {var} TODOC
    * @return {var} TODOC
    */
   has : function(vObject) {
     return this._objects[vObject.toHashCode()] !== null;
   },  

   /**
    * TODOC
    *
    * @return {var} TODOC
    */
   getAll : function() {
     return this._objects;
   },

		/**
		 * Get managed object by name or reference or return null if it does does not exist
		 * @param ref {String|Object} name of object or object reference
		 * @return {Object|Null}
		 */
		getObject : function (ref)
		{
			if ( typeof ref == "object" )
			{
				var obj = this.get(ref); 
				return obj ? obj : null;
			}	
			else if ( typeof ref == "string" )
			{
				var hashCode = this._index[ref];
				return hashCode ? this.getAll()[hashCode] : null;
			}
			
			return null;
		},
		
		/**
		 * get managed object by name or return null if it does does not exist
		 * @param ref {String} name of object 
		 * @return {Object|Null}
		 */
		getByName : function (ref)
		{
			if ( typeof ref != "string" )
			{
				this.error ("getByName requires string argument!")
			}
      var hashCode = this._index[ref];
      return hashCode ? this.getAll()[hashCode] : null;			
		},

		/**
		 * get object name or null if object does not exist
		 * @param ref {String|Object} name of object or object reference
		 * @return {String|Null}
		 */
		getNamedId : function (ref)
		{			
			var obj = this.getObject(ref);
			return obj ? obj.getNamedId() : null;
		},
		
		/**
		 * get a list of names of the managed objects
		 * @return {Array}
		 */
		getNamedIds : function()
		{
			var objects = this.getAll();
			var names = [];
			for (var key in objects)
			{
				names.push(objects[key].getNamedId());
			}
			return names;
		},
		
		/**
		 * Creates a managed object or retrieves it if an object with the same name already
		 * exists. 
		 * @param name {String} Element name
		 * @return {Object} Reference to created or existing object
		 */
		create : function ( name )
		{
      
			if ( typeof name != "string" )
			{
				this.warn ("Argument for create method must be a string, got '" + name + "'.");
				return;
			}
			
			var obj = this.getObject(name); 
			if ( ! qx.lang.Type.isObject( obj )  )
			{ 
        obj = new qcl.access[ this._type ](name);
        if ( ! qx.lang.Type.isObject( obj ) )
        {
          this.error("Could not create object");
        }
			}
			return obj;
		},
		
		/**
		 * deletes all managed objects
		 */
		deleteAll : function()
		{
			var objects = this.getAll();
			for ( var hashCode in objects )
			{
				if ( objects[hashCode] )
				{
					objects[hashCode].dispose();	
				}
				delete objects[hashCode];
				objects = []; 	
			}
		}
  },
  
  /*
  *****************************************************************************
     DEFERRED EXECUTION
  *****************************************************************************
  */  
  
  /**
   * This defer key is used to declare a couple of functions in the "qcl" scope.
   * @ignore(qcl.*)
   */
  defer : function()
  {
    /**
     * convenience converter method
     */
    qcl.bool2visibility = function(state)
    {
      return state ? 'visible' : 'excluded';
    };
    
    qcl.utf8_encode = function ( string )
    {
      return unescape( encodeURIComponent( string ) );
    }

    qcl.utf8_decode = function( string )
    {
      return decodeURIComponent( escape( string ) );
    }
    
    qcl.html_entity_decode = function(str) 
    {
      var ta=document.createElement("textarea");
      ta.innerHTML=str.replace(/</g,"&lt;").replace(/>/g,"&gt;");
      return ta.value;
    }
    
    qcl.strip_tags = function (html)
    {
      return html.replace(/(<([^>]+)>)/ig,"");
    }
    
    qcl.br2nl = function( html )
    {
      return html.replace(/<br[\s]*\/?>/ig,"\n");
    }    
  },  

  /*
  *****************************************************************************
     DESTRUCTOR
  *****************************************************************************
  */

  destruct : function()
  {
    this._disposeArray("_index");	
    this._disposeMap("_objects");  
  }
});

