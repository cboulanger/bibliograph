/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2014 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

/**
 * This class demonstrates how to define unit tests for your application.
 *
 * Execute <code>generate.py test</code> to generate a testrunner application 
 * and open it from <tt>test/index.html</tt>
 *
 * The methods that contain the tests are instance methods with a 
 * <code>test</code> prefix. You can create an arbitrary number of test 
 * classes like this one. They can be organized in a regular class hierarchy, 
 * i.e. using deeper namespaces and a corresponding file structure within the 
 * <tt>test</tt> folder.
 */
qx.Class.define("qcl.test.Data",
{
  extend : qx.dev.unit.TestCase,

  members :
  {
    /*
    ---------------------------------------------------------------------------
      TESTS
    ---------------------------------------------------------------------------
    */
  
    /**
     * Here are some simple tests
     */
    testSimple : function()
    {
      this.assertEquals(4, 3+1, "This should never fail!");
      this.assertFalse(false, "Can false be true?!");
    },

    /**
     * Here are some more advanced tests
     */
    testAdvanced: function () 
    {
      var a = 3;
      var b = a;
      this.assertIdentical(a, b, "A rose by any other name is still a rose");
      this.assertInRange(3, 1, 10, "You must be kidding, 3 can never be outside [1,10]!");
    },
    
    testEventBubbling : function()
    {
      /*
       * we load a static json file which mimicks a json-rpc response
       */
      var url = "../../source/class/qcl/test/data.json";
      var store = new qcl.data.store.JsonRpc(
          /*url*/ url,
          /*serviceName*/ "fakeServiceName", 
          /*marshaler*/ null, 
          /*delegate*/ null, 
          /*rpc */ new qx.io.remote.Rpc()
      );
      store.load("fakeMethodName",[],function(data){
        var model = store.getModel();
        console.log("Adding change bubble listener...");
        model.addListener("changeBubble",function(e){
          console.log( e.getData() );
        },this);
        model.setStringValue("bla!");
        model.getMapValue().setFoo("bar");
        model.getArrayValue().setItem(3,"zzz");
        model.setBoolValue(false);
      },this);
    }
    
  }
});
