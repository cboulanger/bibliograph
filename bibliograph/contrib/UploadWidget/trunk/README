===== UploadWidget =====

Copyright:
   2007 Visionet GmbH, http://www.visionet.de
   2010 OETIKER+PARTNER AG, http://www.oetiker.ch

License:
   LGPL: http://www.gnu.org/licenses/lgpl.html
   EPL: http://www.eclipse.org/org/documents/epl-v10.php
   See the LICENSE file in the project's top-level directory for details.

Authors:
   * Dietrich Streifert (level420)
   * Petr Kobalicek (e666e)
   * Tobi Oetiker (oetiker)

UploadForm and UploadFile Implementation.

The class UploadForm creates a hidden iframe which is used as a target for the 
form submit.

An event of type "sending" is fired after submit. On completion (iframe 
completed loading) a "completed" event is fired.

UploadForm implements the methods getIframeTextContent(), 
getIframeHtmlContent() and getIframeXmlContent() to get the content of the 
iframe.

UploadFile fires a "changeValue" event after the selection by the OS file 
selector is completed.

Multiple UploadFile instances are possible. The text field is read-only.

The differences to uploadwidget to qooxdoo 0.7.3:
- name property renamed to fieldName
- value property renamed to fieldValue
- request method can be only "POST" now
- encoding can be only "multipart/form-data"

I think that "POST" and "multipart/form-data" only is not really limitation. I never used something else in my life. Enjoy this port!

Example:

var form, upload;

form = new uploadwidget.UploadForm("upload", "/url/");
form.setLayout(new qx.ui.layout.HBox());
form.addListener("completed", function(e) 
{ 
  alert("complete");
});

upload = new uploadwidget.UploadButton("file", "Upload", "/yourIcon.png");
upload.addListener("changeFileName", function(e)
{
  if (e.getData() != "") form.send();
});
form.add(upload);

Example how add UploadButton to toolbar:

var upload;

upload = new uploadwidget.UploadButton("file", "Upload", "/yourIcon.png");
upload.set({appearance : "toolbar-button"});
