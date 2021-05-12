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
  
  extend: qx.core.Object,
  
  construct: function () {
    this.base(arguments);
    this._index = {};
    this._objects = {};
  },
  
  members:
  {
    _index: null,
    _objects: null,
    _managedObjectClassName: null,
    _instance: null,
    
    /**
     * Adds managed object
     *
     * @param vObject {var} TODOC
     * @return {void | Boolean} TODOC
     */
    add: function (vObject) {
      let hashCode = vObject.toHashCode();
      this._objects[hashCode] = vObject;
      this._index[vObject.getNamedId()] = hashCode;
    },
    
    /**
     * Removes managed object
     *
     * @param vObject {var} TODOC
     * @return {void | Boolean} TODOC
     */
    remove: function (vObject) {
      let hashCode = vObject.toHashCode();
      delete this._objects[hashCode];
      delete this._index[vObject.getNamedId()];
      return true;
    },
    
    /**
     * Checks if object is already managed
     * @param vObject {var} TODOC
     * @return {var} TODOC
     */
    has: function (vObject) {
      return this._objects[vObject.toHashCode()] !== undefined;
    },
    
    /**
     * Returns all object as an array
     *
     * @return {Array}
     */
    getAll: function () {
      return Object.values(this._objects);
    },
    
    /**
     * Get managed object by name or reference or return null if it does does not exist
     * @param ref {String|Object} name of object or object reference
     * @return {Object|Null}
     */
    getObject: function (ref) {
      if (typeof ref === "object") {
        let obj = this.get(ref);
        return obj ? obj : null;
      } else if (typeof ref === "string") {
        let hashCode = this._index[ref];
        return hashCode ? this._objects[hashCode] : null;
      }
      return null;
    },
    
    /**
     * get managed object by name or return null if it does does not exist
     * @param ref {String} name of object
     * @return {Object|Null}
     */
    getByName: function (ref) {
      if (typeof ref !== "string") {
        this.error("getByName requires string argument!")
      }
      let hashCode = this._index[ref];
      return hashCode ? this._objects[hashCode] : null;
    },
    
    /**
     * get object name or null if object does not exist
     * @param ref {String|Object} name of object or object reference
     * @return {String|Null}
     */
    getNamedId: function (ref) {
      let obj = this.getObject(ref);
      return obj ? obj.getNamedId() : null;
    },
    
    /**
     * get a list of names of the managed objects
     * @return {Array}
     */
    getNamedIds: function () {
      return Object.values(this._objects).map(obj => obj.getNamedId());
    },
    
    /**
     * Creates a managed object or retrieves it if an object with the same name already
     * exists.
     * @param name {String} Element name
     * @return {Object} Reference to created or existing object
     */
    create: function (name) {
      if (typeof name !== "string") {
        throw new TypeError("Argument for create method must be a string.");
      }
      let obj = this.getObject(name);
      if (!qx.lang.Type.isObject(obj)) {
        obj = new qcl.access[this._type](name);
        if (!qx.lang.Type.isObject(obj)) {
          throw new Error("Could not create object");
        }
        obj.setManager(this);
        this.add(obj);
      }
      return obj;
    },
    
    /**
     * deletes all managed objects
     */
    deleteAll: function () {
      Object.values(this._objects).forEach(obj => {
        this.remove(obj);
        obj.dispose();
      });
      this._objects = {};
      this._index = {};
    }
  },
  
  destruct: function () {
    this.deleteAll();
  }
});
