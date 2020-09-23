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
 * Mixin providing methodds to deal with child widgets
 */
qx.Mixin.define("qcl.ui.MChildWidget",
{
  members :
  {
    /**
     * Adds child views to given parent widgets by calling methods of the class
     * that are named similar to "createFooView", "foo" being the id of the
     * child view. The method must return a map with ids that map the childViews
     * to the parentViews.
     *
     * @param {Object} parentViews A map of instances of widget classes that have a
     *    `add()` method. The keys are the ids that determine which child widget
     *    will be added to which parent
     * @param {Array} childData An array of maps, each containing at least an `name`
     *    property with a string value.
     */
    addChildViews: function(parentViews, childData) {
      qx.core.Assert.assertMap(parentViews);
      qx.core.Assert.assertArray(childData);
      for (let {name} of childData) {
        let methodName = "create" + name.charAt(0).toUpperCase() + name.substr(1) + "View";
        if (typeof this[methodName] != "function") {
          this.error(`Cannot add child view ${name}: method ${methodName}() does not exist.`);
        }
        let childViews = this[methodName]();
        let expected = Object.entries(parentViews).length;
        if (!qx.lang.Type.isObject(childViews) || Object.keys(childViews).length !== expected) {
          this.error(`Method ${methodName}() does not return an map with ${expected} entries.`);
          continue;
        }
        for (let [childId, childView] of Object.entries(childViews)) {
          let parentView = parentViews[childId];
          if (!parentView) {
            this.error(`Child id "${childId}" does not match any parent id.`);
            continue;
          }
          if (typeof parentView.add != "function") {
            this.error(`Parent view ${parentView} (id "${childId}") does not have an add() method.`);
          }
          parentView.add(childView);
          this.addOwnedQxObject(childView, `${name}-${childId}`);
        }
      }
    }
  }
});
