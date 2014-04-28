/*******************************************************************************
 *
 * Bibliograph: Online Collaborative Reference Management
 *
 * Copyright: 2007-2014 Christian Boulanger
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
 * @ignore(qcl.bool2visibility)
 */
qx.Class.define("bibliograph.ui.item.ReferenceEditorUi",
{
  extend : bibliograph.ui.item.ReferenceEditor,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
      var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
      var qxComposite1 = this;
      this.setLayout(qxVbox1)
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
      qxMenuBar1.add(qxSpacer1, {
        flex : 1
      });
      var stackView = new qx.ui.container.Stack();
      this.stackView = stackView;
      qxComposite1.add(stackView, {
        flex : 1
      });
      stackView.addListener("appear", this._on_appear, this);
      var qxVbox2 = new qx.ui.layout.VBox(null, null, null);
      var mainPage = new qx.ui.container.Composite();
      mainPage.setLayout(qxVbox2)
      mainPage.setPadding(5);
      stackView.add(mainPage);
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
      var qxHbox1 = new qx.ui.layout.HBox(5, null, null);
      var aboutPage = new qx.ui.container.Composite();
      aboutPage.setLayout(qxHbox1)
      aboutPage.setPadding(5);
      stackView.add(aboutPage);
      qxHbox1.setSpacing(5);
      var qxVbox3 = new qx.ui.layout.VBox(5, null, null);
      var qxComposite2 = new qx.ui.container.Composite();
      qxComposite2.setLayout(qxVbox3)
      aboutPage.add(qxComposite2, {
        flex : 2
      });
      qxVbox3.setSpacing(5);
      var qxLabel1 = new qx.ui.basic.Label(this.tr('Abstract'));
      qxLabel1.setValue(this.tr('Abstract'));
      qxComposite2.add(qxLabel1);
      var qxTextarea1 = new qx.ui.form.TextArea(null);
      qxComposite2.add(qxTextarea1, {
        flex : 1
      });
      this.bind("store.model.abstract", qxTextarea1, "value", {
        converter : this._metadata_observer_converter
      });
      qxTextarea1.addListener("changeValue", function(e) {
        this._on_metadata_changeValue("abstract", e);
      }, this);
      var qxVbox4 = new qx.ui.layout.VBox(5, null, null);
      var qxComposite3 = new qx.ui.container.Composite();
      qxComposite3.setLayout(qxVbox4)
      aboutPage.add(qxComposite3, {
        flex : 1
      });
      qxVbox4.setSpacing(5);
      var qxLabel2 = new qx.ui.basic.Label(this.tr('Keywords'));
      qxLabel2.setValue(this.tr('Keywords'));
      qxComposite3.add(qxLabel2);
      var keywordsTextArea = new qx.ui.form.TextArea(null);
      qxComposite3.add(keywordsTextArea, {
        flex : 1
      });
      this.bind("store.model.keywords", keywordsTextArea, "value", {
        converter : this._metadata_observer_converter
      });
      keywordsTextArea.addListener("changeValue", function(e) {
        this._on_metadata_changeValue("keywords", e);
      }, this);
      var controller = new qcl.data.controller.AutoComplete(null, keywordsTextArea, "\n");
      var store = new qcl.data.store.JsonRpc(null, "bibliograph.reference");
      store.setAutoLoadMethod("getAutoCompleteData");
      controller.bind("input", store, "autoLoadParams", {
        'converter' : qx.lang.Function.bind(function(input) {
          return input ? [this.getDatasource(), "keywords", input] : null
        }, this)
      });
      store.bind("model", controller, "model");
      var qxVbox5 = new qx.ui.layout.VBox(5, null, null);
      var qxComposite4 = new qx.ui.container.Composite();
      qxComposite4.setLayout(qxVbox5)
      aboutPage.add(qxComposite4, {
        flex : 2
      });
      qxVbox5.setSpacing(5);
      var qxLabel3 = new qx.ui.basic.Label(this.tr('Notes'));
      qxLabel3.setValue(this.tr('Notes'));
      qxComposite4.add(qxLabel3);
      var qxTextarea2 = new qx.ui.form.TextArea(null);
      qxComposite4.add(qxTextarea2, {
        flex : 1
      });
      this.bind("store.model.note", qxTextarea2, "value", {
        converter : this._metadata_observer_converter
      });
      qxTextarea2.addListener("changeValue", function(e) {
        this._on_metadata_changeValue("note", e);
      }, this);
      var qxVbox6 = new qx.ui.layout.VBox(5, null, null);
      var contentsPage = new qx.ui.container.Composite();
      contentsPage.setLayout(qxVbox6)
      contentsPage.setPadding(5);
      stackView.add(contentsPage);
      qxVbox6.setSpacing(5);
      var qxLabel4 = new qx.ui.basic.Label(this.tr('Contents'));
      qxLabel4.setValue(this.tr('Contents'));
      contentsPage.add(qxLabel4);
      var qxTextarea3 = new qx.ui.form.TextArea(null);
      contentsPage.add(qxTextarea3, {
        flex : 1
      });
      this.bind("store.model.contents", qxTextarea3, "value", {
        converter : this._metadata_observer_converter
      });
      qxTextarea3.addListener("changeValue", function(e) {
        this._on_metadata_changeValue("contents", e);
      }, this);
      var recordInfoPage = new bibliograph.ui.item.RecordInfoUi();
      recordInfoPage.setVisibility("hidden");
      stackView.add(recordInfoPage);
      recordInfoPage.setUserData("name", "recordInfo");
      var duplicatesViewPage = new bibliograph.ui.item.DuplicatesViewUi();
      duplicatesViewPage.setVisibility("hidden");
      stackView.add(duplicatesViewPage);
      duplicatesViewPage.setUserData("name", "recordInfo");
      var menuBar = new qx.ui.menubar.MenuBar();
      this.menuBar = menuBar;
      menuBar.setEnabled(false);
      menuBar.setHeight(18);
      qxComposite1.add(menuBar);
      var mainButton = new qx.ui.menubar.Button(this.tr('Main'), null, null);
      mainButton.setRich(true);
      mainButton.setLabel(this.tr('Main'));
      menuBar.add(mainButton);
      mainButton.addListener("click", function(e) {
        this._showStackViewPage(mainPage, mainButton)
      }, this);
      var aboutButton = new qx.ui.menubar.Button(this.tr('About'), null, null);
      aboutButton.setRich(true);
      aboutButton.setLabel(this.tr('About'));
      menuBar.add(aboutButton);
      aboutButton.addListener("click", function(e) {
        this._showStackViewPage(aboutPage, aboutButton)
      }, this);
      var contentsButton = new qx.ui.menubar.Button(this.tr('Contents'), null, null);
      contentsButton.setRich(true);
      contentsButton.setVisibility("excluded");
      contentsButton.setLabel(this.tr('Contents'));
      menuBar.add(contentsButton);
      contentsButton.addListener("click", function(e) {
        this._showStackViewPage(contentsPage, contentsButton)
      }, this);
      this.addListener("changeReferenceType", function(e)
      {
        var refType = e.getData();
        contentsButton.setVisibility((refType == "book" || refType == "collection") ? "visible" : "excluded");
      }, this);
      var recordInfoButton = new qx.ui.menubar.Button(this.tr('Record Info'), null, null);
      recordInfoButton.setRich(true);
      recordInfoButton.setLabel(this.tr('Record Info'));
      menuBar.add(recordInfoButton);
      recordInfoButton.addListener("click", function(e) {
        this._showStackViewPage(recordInfoPage, recordInfoButton)
      }, this);
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("reference.remove").bind("state", recordInfoButton, "visibility", {
        converter : qcl.bool2visibility
      });
      var duplicatesButton = new qx.ui.menubar.Button(this.tr('Duplicates'), null, null);
      duplicatesButton.setRich(true);
      duplicatesButton.setLabel(this.tr('Duplicates'));
      menuBar.add(duplicatesButton);
      duplicatesButton.addListener("click", function(e) {
        this._showStackViewPage(duplicatesViewPage, duplicatesButton)
      }, this);
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("reference.remove").bind("state", duplicatesButton, "visibility", {
        converter : qcl.bool2visibility
      });
    }
  }
});
