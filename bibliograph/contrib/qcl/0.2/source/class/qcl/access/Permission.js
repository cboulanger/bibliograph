/* ************************************************************************

   qooxdoo - the new era of web development

   http://qooxdoo.org

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

/* ************************************************************************
#require(qcl.access.PermissionManager)
************************************************************************ */

/**
 * A permission object. The object has a "granted" and a read-only "state" property. The "granted" property 
 * is set to true if the current user in priciple has the property. However, you
 * can attach condition functions to this object by the addCondition method. Only
 * if the "granted" property AND all of these conditions return true, the "state" 
 * property will be true.
 */
qx.Class.define("qcl.access.Permission",
{
  extend : qx.core.Object,

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  construct : function(vName)
  {
    this.base(arguments);
    this.setNamedId(vName);
    this.__conditions = [];
    this.__state = false;
    this._manager = qx.core.Init.getApplication().getAccessManager().getPermissionManager();
    this._manager.add(this);
  },

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  properties :
  {
    /**
     * Name of the permission. Should be a dot-separated name such as myapp.permissions.email.delete
     */
    namedId :
    {
      check:    "String",
      nullable: false
    },

    /**
     * A description of the permission, optional
     */
    description :
    {
      check: "String",
      nullable: true
    },
    
    /**
     * Whether the permission is granted at all. A permission's
     * state is true if it is granted and if all conditions 
     * evaluate true
     */
    granted :
    {
      check : "Boolean",
      init : false,
      event : "changeGranted",
      apply : "_applyGranted"
    }
    
    
  },
  
  /*
  *****************************************************************************
     EVENTS
  *****************************************************************************
  */

  events :
  {
    "changeState"   : "qx.event.type.Data"
  },
  
  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */

  members :
  {
    
    _manager : null,
    
    /**
     * get all conditions
     * @return {Array}
     */
    getConditions : function()
    {
      return this.__conditions;
    },
    
    /**
     * adds a condition for the permisson
     *
     * @param conditionFunc {Function} callback function
     * @param context {Object} The execution context of the callback (i.e. "this")
     * @return {Boolean} Success
     */
    addCondition : function(conditionFunc, context)
    {
      if (typeof conditionFunc != "function")
      {
        this.error("No callback function supplied!");
        return false;
      }

      if (this.hasCondition(conditionFunc,context))
      {
        this.warn("Condition has already been added.");
        return false;
      }
      else
      {
        // add a condition
        this.getConditions().push({
          'condition' : conditionFunc,
          'context'   : context || null
        });

        return true;
      }
    },


    /**
     * checks if condition has already been added      
     * 
     * @param conditionFunc {Function} Callback Function
     * @param context {Object} execution context
     * @return {Boolean} Whether condition has been added
     */
    hasCondition : function(conditionFunc, context)
    {
      var conditions = this.getConditions(); 
      for (var i=0; i<conditions.length; i++)
      {
        if (  conditionFunc 
            && typeof conditionFunc == "object" 
            && conditions[i].condition == conditionFunc 
            && conditions[i].context == (context || null)) 
        {
          return true;
        }
      }

      return false;
    },


    /**
     * remove a condition
     *
     * @param conditionFunc {Function} Callback Function
     * @param context {Object} execution context
     * @return {Boolean} Whether condition was removed or not
     */
    removeCondition : function(conditionFunc, context)
    {
      var conditions = this.getConditions();

      for (var i=0; i<conditions.length; i++)
      {
        if (conditions[i].condition == conditionFunc 
            && conditions[i].context == (context || null)) 
        {
          conditions.splice(i, 1);
          return true;
        }
      }
      return false;
    },
    
    /**
     * Checks if all conditions are satisfied. Only those conditions
     * are 
     * @param context {Object} If provided, check only those conditions
     * with a matching context
     * @return {Boolean} Returns true if all conditions are satisfied
     */
    _satifiesAllConditions : function(context)
    {
      var conditions = this.getConditions();
      //console.log("Checking conditions for " + this.getNamedId() + ", context " + context );
      
      /*
       * loop through all conditions 
       */
      for (var i=0; i<conditions.length; i++)
      {
        var condFunc    = conditions[i].condition; 
        var condContext = conditions[i].context;
        
        //console.log([condFunc,condContext]);
        
        /*
         * check condition only if context matches,
         * unless no context has been passed
         */
        if ( ! context || context == condContext) 
        {
          if ( ! condFunc.call(condContext) )
          {
            return false;
          } 
        }
      }
      return true;  
    },

    /**
     * Applies the permission grant. if the state has changed, 
     * dispatches changeState event.
     * @param granted {Boolean}
     * @param old {Boolean}
     */
    _applyGranted : function( granted, old )
    {

      /*
       * if this is a wildcard permission, set all dependent permissions
       */
      var myName = this.getNamedId(); 
      var pos = myName.indexOf("*");
      if ( pos > -1 )
      {
        this._manager.getNamedIds().forEach(function(name)
        {
          if (pos == 0 || myName.substr(0, pos) == name.substr(0, pos))
          {
            if ( name.indexOf("*") < 0) // other wildcard permissions do not need to be updated
            {
              try{
                this._manager.getByName(name).setGranted(granted);
              }catch(e){
               this.warn("Invalid manager:"+this._manager); 
              }
            }
          } 
        },this);
      }

      /*
       * update state
       */
      var state = this.getState(); 
      this.fireDataEvent( "changeState", state );
    },

    /**
     * Gets the state of the permission. Returns true if the 
     * permission has been granted in general to the particular
     * user and if all condition functions that have been attached
     * return true.
     * @param context {Object} If provided, check only the conditions 
     * that have a matching object context. This allows to reuse permissions
     * in different instances.
     * @return {Boolean} The state of the permission 
     */
    getState : function(context)
    {
      return this.isGranted() && this._satifiesAllConditions(context); 
    },
    
    /**
     * dummy function for databinding
     * @return {void}
     */
    resetState : function()
    {
      // do nothing
    },
    
    /**
     * Updates the current state and dispatches events
     * @param context {Object} If provided, check only the conditions 
     * that have a matching object context. This allows to reuse permissions
     * in different instances.
     * @return {Boolean} The state of the permission 
     */
    update : function(context)
    {
      var state = this.getState(context);
      //console.log("Updating "+ this.getNamedId() + ": " + state);
      this.fireDataEvent( "changeState", state );
    }   
    
  },

  /*
  *****************************************************************************
     DESTRUCTOR
  *****************************************************************************
  */
  destruct : function() {
    this._disposeArray("__conditions");
    this._manager.remove(this);
  }
});