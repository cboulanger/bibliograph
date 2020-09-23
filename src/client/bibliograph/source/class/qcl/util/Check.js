qx.Class.define("qcl.util.Check", {
  type: "static",
  statics: {
    isNumberOrString(value) {
      return typeof value == "string" ||
        typeof value == "number";
    },
    isNumberOrStringNullable(value) {
      return qcl.util.Check.isNumberOrString(value) ||
        value === null;
    },
    isScalar(value) {
      return qcl.util.Check.isNumberOrString(value) ||
        typeof value == "boolean";
    },
    isScalarNullable(value) {
      return qcl.util.Check.isScalar(value) || value === null;
    }
  }
});
