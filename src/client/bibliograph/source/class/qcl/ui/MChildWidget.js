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
/*global qx qcl */

/**
 * Mixin providing methodds to deal with child widgets
 */
qx.Mixin.define("qcl.ui.MChildWidget",
{
  members :
  {
    /**
     * Adds child views to given parent widgets by calling methods of the class
     * that are named similar to "createFooView", "foo" being the id of the
     * child view. The method must return an array of childViews that matches in 
     * length to the number of passed parents.
     * @param {Array} parentViews An array of instances of widget classes that have a 
     *    `add()` method.  
     * @param {Array} childData An array of maps, each containing at least an `id` 
     *    property with a string value
     * @param {String} widgetIdPath The path to the child view. The id will be appended
     *    to it, separated with a slash. 
     */
    addChildViews: function( parentViews, childData, widgetIdPath )
    {
      qx.core.Assert.assertArray( parentViews );
      qx.core.Assert.assertArray( childData );
      for( let {id} of childData ){
        let methodName = 'create' + id.charAt(0).toUpperCase() + id.substr(1) + "View";
        if( typeof this[methodName] != "function" ){
          this.error( `Cannot add child view ${id}: method ${methodName}() does not exist.`);
        }
        let childViews = this[methodName]();
        if( ! qx.lang.Type.isArray(childViews) || childViews.length !== parentViews.length ){
          this.error( `Method ${methodName}() does not return an array of length ${parentViews.length}.`);
        }
        let i=0;
        for( let childView of childViews){
          let parentView = parentViews[i++];
          if( typeof parentView.add != "function"){
            this.error(`${parentView} does not have an add() method.`);
          }
          parentView.add(childView);
          if( widgetIdPath ){
            childView.setWidgetId(`${widgetIdPath}/${id}`.replace(/\/\//g,"/"));
          }
        }
      }
    } 
  }
});