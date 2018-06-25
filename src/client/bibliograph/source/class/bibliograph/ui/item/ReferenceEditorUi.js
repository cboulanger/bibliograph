/*******************************************************************************
 *
 * Bibliograph: Online Collaborative Reference Management
 *
 * Copyright: 2007-2015 Christian Boulanger
 *
 * License: LGPL: http://www.gnu.org/licenses/lgpl.html EPL:
 * http://www.eclipse.org/org/documents/epl-v10.php See the LICENSE file in the
 * project's top-level directory for details.
 *
 * Authors: Christian Boulanger (cboulanger)
 *
 ******************************************************************************/

/*global qx qcl bibliograph*/

/**
 * The reference editor view 
 * @ignore(bibliograph.Utils.bool2visibility)
 */
qx.Class.define("bibliograph.ui.item.ReferenceEditorUi",
{
  extend : bibliograph.ui.item.ReferenceEditor,
  include : [qcl.ui.MChildWidget],
  construct : function()
  {
    this.base(arguments);
    var permMgr = this.getApplication().getAccessManager().getPermissionManager();

    // container
    var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
    var qxComposite1 = this;
    this.setLayout(qxVbox1)

    // Top menu bar with status label
    var qxMenuBar1 = new qx.ui.menubar.MenuBar();
    qxComposite1.add(qxMenuBar1);
    var itemViewTitleLabel = new qx.ui.basic.Label(null);
    itemViewTitleLabel.setPadding(3);
    itemViewTitleLabel.setRich(true);
    itemViewTitleLabel.setValue(null);
    qxMenuBar1.add(itemViewTitleLabel, {
      flex : 2
    });
    qx.event.message.Bus.getInstance().subscribe("logout", function(e) {
      itemViewTitleLabel.setValue("");
    }, this)
    var qxSpacer1 = new qx.ui.core.Spacer(null, null);
    qxMenuBar1.add(qxSpacer1, { flex : 1 });
    var statusLabel = new qx.ui.basic.Label(null);
    this._statusLabel = statusLabel;
    statusLabel.setTextColor("#808080");
    statusLabel.setMargin(5);
    qxMenuBar1.add(statusLabel);

    // Reference editor stack view
    var stackView = new qx.ui.container.Stack();
    this.stackView = stackView;
    this.setWidgetId("app/item/editor/stack");
    qxComposite1.add(stackView, {  flex : 1 });
    stackView.addListener("appear", this._on_appear, this);
    
    // Bottom menu bar
    var menuBar = new qx.ui.menubar.MenuBar();
    this.menuBar = menuBar;
    menuBar.setEnabled(false);
    menuBar.setHeight(18);
    qxComposite1.add(menuBar);

    // add child controls
    let childControls = [
      { id : "main" },
      { id : "extended" },
      { id : "toc" },
      // @todo test & enable { id : "info" },
      // @todo test & enable { id : "duplicates" }
      // formatted view is not part of the stack
    ];
    this.addChildViews( [stackView,menuBar], childControls, "app/item/editor" );
  },

  members: 
  {

    /**
     * Create main form page 
     */
    createMainView : function()
    {
      // page
      var mainPage = new qx.ui.container.Composite();
      var qxVbox2 = new qx.ui.layout.VBox();
      mainPage.setLayout(qxVbox2)
      mainPage.setPadding(5);
      var qxScroll1 = new qx.ui.container.Scroll();
      qxScroll1.setScrollbarY("on");
      mainPage.add(qxScroll1, {
        flex : 1
      });
      var formStack = new qx.ui.container.Stack();
      this.formStack = formStack;
      formStack.setPadding(5);
      formStack.setHeight(500);
      qxScroll1.add(formStack);

      // button 
      var mainButton = new qx.ui.menubar.Button(this.tr('Main'), null, null);
      mainButton.setRich(true);
      mainButton.setLabel(this.tr('Main'));
      this._addStackViewPage("main",mainPage, mainButton);
      mainButton.addListener("click", function(e) {
        this._showStackViewPage("main")
      }, this);

      return  [ mainPage, mainButton ];
    },    

    /**
     * Created the second form page with extended information
     * (abstract, keyswords, notes)
     */
    createExtendedView : function()
    {
      var aboutPage = new qx.ui.container.Composite();
      var qxHbox1 = new qx.ui.layout.HBox(5, null, null);
      aboutPage.setLayout(qxHbox1)
      aboutPage.setPadding(5);
      qxHbox1.setSpacing(5);

      // Abstract
      var qxVbox3 = new qx.ui.layout.VBox(5, null, null);
      var qxComposite2 = new qx.ui.container.Composite();
      qxComposite2.setLayout(qxVbox3)
      aboutPage.add(qxComposite2, {
        flex : 2
      });
      qxVbox3.setSpacing(5);
      var qxLabel1 = new qx.ui.basic.Label(this.tr('Abstract'));
      qxComposite2.add(qxLabel1);
      var qxTextarea1 = new qx.ui.form.TextArea(null);
      qxComposite2.add(qxTextarea1, { flex : 1 });
      this._setupTextArea(qxTextarea1,"abstract");

      // Keywords
      var qxVbox4 = new qx.ui.layout.VBox(5, null, null);
      var qxComposite3 = new qx.ui.container.Composite();
      qxComposite3.setLayout(qxVbox4)
      aboutPage.add(qxComposite3, {
        flex : 1
      });
      qxVbox4.setSpacing(5);
      var qxLabel2 = new qx.ui.basic.Label(this.tr('Keywords'));
      qxComposite3.add(qxLabel2);
      var keywordsTextArea = new qx.ui.form.TextArea(null);
      qxComposite3.add(keywordsTextArea, { flex : 1 });

      this._setupTextArea(keywordsTextArea,"keywords");
      this._setupAutocomplete(keywordsTextArea,"keywords","\n");

      // Notes
      var qxVbox5 = new qx.ui.layout.VBox(5, null, null);
      var qxComposite4 = new qx.ui.container.Composite();
      qxComposite4.setLayout(qxVbox5)
      aboutPage.add(qxComposite4, { flex : 2 });
      qxVbox5.setSpacing(5);
      var qxLabel3 = new qx.ui.basic.Label(this.tr('Notes'));
      qxComposite4.add(qxLabel3);
      var qxTextarea2 = new qx.ui.form.TextArea(null);
      qxComposite4.add(qxTextarea2, { flex : 1 });

      this._setupTextArea(qxTextarea2,"note");

      // button
      var aboutButton = new qx.ui.menubar.Button(this.tr('About'), null, null);
      aboutButton.setRich(true);
      aboutButton.setLabel(this.tr('About'));
      this._addStackViewPage("about",aboutPage, aboutButton);
      aboutButton.addListener("click", function(e) {
        this._showStackViewPage("about")
      }, this);

      return [ aboutPage, aboutButton ];
    },

    /**
     * Create a field for tables of contents 
     */
    createTocView : function()
    {
      var qxVbox6 = new qx.ui.layout.VBox(5, null, null);
      var contentsPage = new qx.ui.container.Composite();
      contentsPage.setLayout(qxVbox6)
      contentsPage.setPadding(5);
      qxVbox6.setSpacing(5);
      var qxLabel4 = new qx.ui.basic.Label(this.tr('Contents'));
      contentsPage.add(qxLabel4);
      var qxTextarea3 = new qx.ui.form.TextArea(null);
      contentsPage.add(qxTextarea3, { flex : 1 });

      this._setupTextArea(qxTextarea3,"contents");

      // button
      var contentsButton = new qx.ui.menubar.Button(this.tr('Contents'), null, null);
      contentsButton.setRich(true);
      contentsButton.setVisibility("excluded");
      contentsButton.setLabel(this.tr('Contents'));
      this._addStackViewPage("contents",contentsPage, contentsButton);
      contentsButton.addListener("click", function(e) {
        this._showStackViewPage("contents")
      }, this);

      // Handler to react to reference Type change
      this.addListener("changeReferenceType", function(e)
      {
        var refType = e.getData();
        contentsButton.setVisibility((refType == "book" || refType == "collection") ? "visible" : "excluded");
      }, this);

      return [ contentsPage, contentsButton ];
    },

    /**
     * Create a record info page
     */
    createInfoView : function()
    {
      // page
      var recordInfoPage = new bibliograph.ui.item.RecordInfoUi();
      recordInfoPage.setVisibility("hidden");
      recordInfoPage.setUserData("name", "recordInfo");

      // button
      var recordInfoButton = new qx.ui.menubar.Button(this.tr('Record Info'), null, null);
      recordInfoButton.setRich(true);
      recordInfoButton.setLabel(this.tr('Record Info'));
      this._addStackViewPage("recordInfo",recordInfoPage, recordInfoButton);
      recordInfoButton.addListener("click", function(e) {
        this._showStackViewPage("recordInfo");
      }, this);
      var permMgr = this.getApplication().getAccessManager().getPermissionManager();
      permMgr.create("reference.remove").bind("state", recordInfoButton, "visibility", {
        converter : bibliograph.Utils.bool2visibility
      });

      return [ recordInfoPage, recordInfoButton ];
    },
    
    createDuplicatesView : function()
    {
      
      // page
      var duplicatesViewPage = new bibliograph.ui.item.DuplicatesViewUi();
      duplicatesViewPage.setVisibility("hidden");
      duplicatesViewPage.setUserData("name", "recordInfo");

      //  button
      var duplicatesButton = new qx.ui.menubar.Button(this.tr('Duplicates'), null, null);
      duplicatesButton.setRich(true);
      duplicatesButton.setLabel(this.tr('Duplicates'));
      this._addStackViewPage("duplicates",duplicatesViewPage, duplicatesButton);
      duplicatesButton.addListener("click", function(e) {
        this._showStackViewPage("duplicates")
      }, this);
      var duplNumLabel = new qx.ui.basic.Label();
      duplicatesButton._addAt(duplNumLabel,1);
      duplicatesViewPage.bind( "numberOfDuplicates", duplNumLabel, "value", {
        converter : function(value){
          duplNumLabel.setMaxWidth(value ? null:0);
          return value ? "(" + value + ")" : null
        }
      });
      // todo: create separate permission for duplicates
      var permMgr = this.getApplication().getAccessManager().getPermissionManager();
      permMgr.create("reference.remove").bind(
          "state", duplicatesButton, "visibility",
          { converter : bibliograph.Utils.bool2visibility }
      );

      return [ duplicatesViewPage, duplicatesButton ];
    }

  }
});
