qx.Class.define("qcl.ui.treevirtual.TreeAction",{
  extend: qx.core.Object,
  construct : function(config){
    this.base(arguments);
    this.set(config);
  },
  properties:{
  
    /**
     * The tree widget in which the action occurs
     */
    tree: {
      check: "qcl.ui.treevirtual.DragDropTree",
      nullable: false
    },
  
    /**
     * The name of the action
     */
    action: {
     check: "String",
      nullable: false
    },
  
    /**
     * The model of the node(s) that are being
     * affected by the action
     */
    model: {
      check: "qx.data.Array",
      nullable: false
    },
  
    /**
     * If the action involves a target (such as move or copy),
     * set the target here
     */
    targetModel: {
      check: "Object",
      nullable: true
    }
  }
});