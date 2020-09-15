/**
 * This class provides converter object for use in `.bind()`
 * statements
 */
qx.Class.define("qcl.util.Converter", {
  type: "static",
  statics: {
    bool2visibility : {
      converter: state => state ? "visible" : "excluded"
    },
    bool2visibilityReverse : {
       converter: state => state ? "excluded": "visible"
    }
  }
});
