/* ************************************************************************

   qooxdoo - the new era of web development

   http://qooxdoo.org

   Copyright:
     2004-2007 1&1 Internet AG, Germany, http://www.1and1.org

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Alexander Steitz (aback)

************************************************************************ */

/* ************************************************************************
#asset(qx/icon/${qx.icontheme}/16/actions/document-save.png)
#asset(qx/icon/${qx.icontheme}/16/actions/dialog-ok.png)
#asset(qx/icon/${qx.icontheme}/16/actions/document-revert.png)
************************************************************************ */
/**
 * uploadWidget Example application
 */
qx.Class.define("uploadwidget.demo.Application",
{
  extend : qx.application.Standalone,




  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */

  members :
  {
    /**
     * Main method - application start point
     *
     * @return {void}
     */
    main : function()
    {
      this.base(arguments);
      
      // Add log appenders
      if (qx.core.Environment.get("qx.debug"))
      {
        qx.log.appender.Native;
        qx.log.appender.Console;
      }
      
      var mainContainer = new qx.ui.container.Composite(new qx.ui.layout.VBox(40));      
      var container = new qx.ui.container.Composite(new qx.ui.layout.VBox(10));
      
      var lbl1Text = "<h1>UploadForm and UploadFile Implementation</h1>" +
      		           "<p>The class UploadForm creates a hidden iframe which is used as a target for the form submit.</p>" +
      		           "<p>An event of type <b>\"sending\"</b> is fired after submit. " +
      		           "On completion (iframe completed loading) a <b>\"completed\"</b> event is fired.</p>" +
      		           "<p>Upload form implements the methods getIframeTextContent, getIframeHtmlContent " +
      		           "and getIframeXmlContent to get the content of the iframe</p>" +
      		           "<p>UploadField and UploadButton fire a <b>\"changeFileName\"</b> event after the selection through the OS fileselector is " +
      		           "completed</p>" +
      		           "<p>Multiple UploadField and UploadButton instances are possible.</p>";
      var label1 = new qx.ui.basic.Label(lbl1Text);
      label1.setRich(true);
      this.getRoot().add(label1, {top:10, left:20});
      
      /*
       * SINGLE UPLOAD WIDGET 
       */      
      var form = new uploadwidget.UploadForm('uploadFrm','/cgi-bin/uploadtest.pl');
      form.setParameter('rm','upload');
      form.setLayout(new qx.ui.layout.Basic);

      container.add(form);
      mainContainer.add(container);

      var file = new uploadwidget.UploadButton('uploadfile', 'Upload Button','icon/16/actions/document-save.png');
      file.setWidth(700);
      file.setHeight(100);
      form.add(file, {left:0,top:0});

      form.addListener('completed',function(e) {
        window.alert('completed');
        form.clear();
        var response = form.getIframeTextContent();
        window.alert('response:'+response);
      });

      form.addListener('sending',function(e) {
        this.debug('sending');
      });

      file.addListener('changeFileName',function(e){
        if(e.getData()!='') {
            window.alert(file.getFileName() + ' - ' + file.getFileSize() + ' Bytes');
            form.send();
        }
      });
      
      
      
      /*
       * MULTIPLE UPLOAD WIDGET 
       */
      
      var container2 = new qx.ui.container.Composite(new qx.ui.layout.VBox(10));
      
      var form2 = new uploadwidget.UploadForm('uploadFrm','uploadtest.cgi');
      form2.setParameter('rm','upload_multiple');
      form2.setPadding(8);

      var vb = new qx.ui.layout.VBox(10)
      form2.setLayout(vb);
      container2.add(form2);
      
      var l = new qx.ui.basic.Label("One UploadForm, three file uploads.<br/>Please select the files and then hit the 'Upload' Button");
      l.setRich(true);
      form2.add(l);
          
      var file1 = new uploadwidget.UploadField('uploadfile1', 'Select File 1','icon/16/actions/document-save.png');
      form2.add(file1);

      var file2 = new uploadwidget.UploadField('uploadfile2', 'Select File 2','icon/16/actions/document-save.png');
      form2.add(file2);

      var file3 = new uploadwidget.UploadField('uploadfile3', 'Select File 3','icon/16/actions/document-save.png');
      form2.add(file3);

      form2.addListener('sending',function(e) {
        this.debug('sending');
      });

      var bt = new qx.ui.form.Button("Upload", "icon/16/actions/dialog-ok.png");
      bt.set({ marginTop : 10, allowGrowX : false });
      form2.add(bt);
      
      form2.addListener('completed',function(e) {
        window.alert('completed');
        file1.setFieldValue('');
        file2.setFieldValue('');
        file3.setFieldValue('');
        var response = this.getIframeHtmlContent();
        window.alert('Response:'+response);
        bt.setEnabled(true);
      });

      var resetButton = new qx.ui.form.Button("Reset", "icon/16/actions/document-revert.png");
      resetButton.set({ width: 100, allowGrowX: false });
      resetButton.addListener("execute", function(e) {
        file1.setFieldValue("");
        file2.setFieldValue("");
        file3.setFieldValue("");
      }, this);
      form2.add(resetButton);

      bt.addListener('execute', function(e) {
        form2.send();
        this.setEnabled(false);
      });

      mainContainer.add(container2);
            
      this.getRoot().add(mainContainer, { top:220, left: 20 });
    }
  }
  
});
