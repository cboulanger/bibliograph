/* ************************************************************************

  Bibliograph. The open source online bibliographic data manager

  http://www.bibliograph.org

  Copyright:
    2003-2020 Christian Boulanger

  License:
    MIT license
    See the LICENSE file in the project's top-level directory for details.

  Authors:
    Christian Boulanger (@cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * Window with information on the application
 * @asset(bibliograph/icon/bibliograph-logo-square.png)
 */
qx.Class.define("bibliograph.ui.window.AboutWindow",
{
  extend : qx.ui.window.Window,
  construct : function() {
    this.base(arguments);
    this.setCaption(this.tr("About Bibliograph"));
    this.setShowMaximize(false);
    this.setWidth(350);
    this.setShowMinimize(false);
    this.setHeight(450);
    this.addListener("appear", function(e) {
      this.center();
    }, this);
    qx.event.message.Bus.getInstance().subscribe("logout", function(e) {
      this.close();
    }, this);
    var qxGrow1 = new qx.ui.layout.Grow();
    this.setLayout(qxGrow1);
    var qxTabView1 = new qx.ui.tabview.TabView(null);
    this.add(qxTabView1);
    var qxPage1 = new qx.ui.tabview.Page(null);
    qxPage1.setLabel(this.tr("The application"));
    qxTabView1.add(qxPage1);
    var qxVbox1 = new qx.ui.layout.VBox(5, null, null);
    qxVbox1.setAlignX("center");
    qxVbox1.setSpacing(5);
    qxPage1.setLayout(qxVbox1);
    var qxImage1 = new qx.ui.basic.Image("bibliograph/icon/bibliograph-logo-square.png");
    qxImage1.setSource("bibliograph/icon/bibliograph-logo-square.png");
    qxPage1.add(qxImage1);
    var qxLabel1 = new qx.ui.basic.Label(null);
    qxLabel1.setRich(true);
    qxLabel1.setValue("<b>Bibliograph Online Bibliographic Data Manager</b>              ");
    qxPage1.add(qxLabel1);
    var qxLabel2 = new qx.ui.basic.Label(this.getApplication().getApplication().getVersion());
    qxLabel2.setValue(this.getApplication().getApplication().getVersion());
    qxPage1.add(qxLabel2);
    var qxLabel3 = new qx.ui.basic.Label(this.getApplication().getApplication().getCopyright());
    qxLabel3.setValue(this.getApplication().getApplication().getCopyright());
    qxPage1.add(qxLabel3);
    var qxPage2 = new qx.ui.tabview.Page(null);
    qxPage2.setLabel(this.tr("Credits"));
    qxTabView1.add(qxPage2);
    var qxVbox2 = new qx.ui.layout.VBox(5, null, null);
    qxVbox2.setAlignX("center");
    qxVbox2.setSpacing(5);
    qxPage2.setLayout(qxVbox2);
    var qxLabel4 = new qx.ui.basic.Label(null);
    qxLabel4.setRich(true);
    qxLabel4.setValue(
        "<p style='font-weight:bold'>Open source libraries</p><ul>"+
          "<li><a href=\"http://www.qooxdoo.org\" target=\"_blank\">qooxdoo</a> JavaScript framework: (c)&nbsp;<a href=\"http://www.1und1.de\">1&1 Internet AG</a></li>"+
          "<li><a href=\"http://citationstyles.org/\" target=\"_blank\">CSL - The Citation Style Language</a>. (c)&nbsp;Bruce D'Arcus and others</li>"+
          "<li><a href=\"http://bytebucket.org/rjerome/citeproc-php\" target=\"_blank\">CiteProc-PHP</a>. (c)&nbsp;Ron Jerome</li>"+
        "<li><a href=\"https://github.com/simar0at/sru-cql-parser\" target=\"_blank\">SRU/QCL Parser</a>. (c)&nbsp;Robert Sanderson</li></ul>"+
        "<p style='font-weight:bold'>Partial funding was provided by</p><ul>"+
          "<li><a href=\"http://www.rewi.hu-berlin.de/\">Juristische Fakultät (Department of Law)</a>,<a href=\"http://www.hu-berlin.de/\">Humboldt-Universität zu Berlin</a>  </li></ul>"+
        "<p style='font-weight:bold'>Acknowledgements</p><ul>"+
            "<li>Documentation & Testing: Julika Rosenstock, Till Rathschlag, Anna Lütkefend</li>"+
            "<li>Application icon: Siarhei Barysiuk</li>"+
            "<li>Coded with <a href=\"http://www.jetbrains.com/webstorm/\" target=\"_blank\">WebStorm JavaScript IDE</a> and "+
            "<a href=\"https://c9.io/\" target=\"_blank\">Cloud9 IDE</a></li>"+
            "</ul>"
      );
    qxPage2.add(qxLabel4);
  }
});
