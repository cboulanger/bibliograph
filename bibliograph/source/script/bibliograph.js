(function(){

if (!window.qx) window.qx = {};

qx.$$start = new Date();

if (!qx.$$environment) qx.$$environment = {};
var envinfo = {"qx.application":"bibliograph.Application","qx.revision":"","qx.theme":"bibliograph.theme.Theme","qx.version":"3.5"};
for (var k in envinfo) qx.$$environment[k] = envinfo[k];

if (!qx.$$libraries) qx.$$libraries = {};
var libinfo = {"__out__":{"sourceUri":"script"},"bibliograph":{"resourceUri":"../source/resource","sourceUri":"../source/class"},"dialog":{"resourceUri":"../contrib/Dialog/master/source/resource","sourceUri":"../contrib/Dialog/master/source/class"},"persist":{"resourceUri":"../contrib/Persist/trunk/source/resource","sourceUri":"../contrib/Persist/trunk/source/class"},"qcl":{"resourceUri":"../contrib/qcl/source/resource","sourceUri":"../contrib/qcl/source/class"},"qx":{"resourceUri":"../../qooxdoo/qooxdoo-3.5-sdk/framework/source/resource","sourceUri":"../../qooxdoo/qooxdoo-3.5-sdk/framework/source/class","sourceViewUri":"https://github.com/qooxdoo/qooxdoo/blob/%{qxGitBranch}/framework/source/class/%{classFilePath}#L%{lineNumber}"},"uploadwidget":{"resourceUri":"../contrib/UploadWidget/trunk/source/resource","sourceUri":"../contrib/UploadWidget/trunk/source/class"},"virtualdata":{"resourceUri":"../contrib/VirtualData/trunk/source/resource","sourceUri":"../contrib/VirtualData/trunk/source/class"}};
for (var k in libinfo) qx.$$libraries[k] = libinfo[k];

qx.$$resources = {};
qx.$$translations = {"C":null,"de":null,"en":null};
qx.$$locales = {"C":null,"de":null,"en":null};
qx.$$packageData = {};
qx.$$g = {}

qx.$$loader = {
  parts : {"boot":[0]},
  packages : {"0":{"uris":["__out__:bibliograph.38f33ad5caba.js","qx:qx/Bootstrap.js","qx:qx/util/OOUtil.js","qx:qx/core/Environment.js","qx:qx/bom/client/EcmaScript.js","qx:qx/lang/normalize/Function.js","qx:qx/lang/normalize/Array.js","qx:qx/Mixin.js","qx:qx/core/Aspect.js","qx:qx/lang/normalize/String.js","qx:qx/lang/normalize/Object.js","qx:qx/Interface.js","qx:qx/lang/normalize/Error.js","qx:qx/lang/normalize/Date.js","qx:qx/core/Property.js","qx:qx/Class.js","qx:qx/data/MBinding.js","qx:qx/data/SingleValueBinding.js","qx:qx/lang/Type.js","qx:qx/core/Assert.js","qx:qx/type/BaseError.js","qx:qx/core/AssertionError.js","qx:qx/dev/StackTrace.js","qx:qx/lang/Array.js","qx:qx/bom/client/Engine.js","qx:qx/lang/Function.js","qx:qx/event/GlobalError.js","qx:qx/core/WindowError.js","qx:qx/core/GlobalError.js","qx:qx/core/ObjectRegistry.js","qx:qx/lang/Json.js","qx:qx/lang/String.js","qx:qx/data/IListData.js","qx:qx/core/ValidationError.js","qx:qx/util/RingBuffer.js","qx:qx/log/appender/RingBuffer.js","qx:qx/log/Logger.js","qx:qx/core/MLogging.js","qx:qx/dom/Node.js","qx:qx/bom/Event.js","qx:qx/bom/Style.js","qx:qx/bom/client/OperatingSystem.js","qx:qx/bom/client/Browser.js","qx:qx/bom/client/CssTransition.js","qx:qx/event/Manager.js","qx:qx/event/IEventHandler.js","qx:qx/event/Registration.js","qx:qx/core/MEvent.js","qx:qx/event/IEventDispatcher.js","qx:qx/core/MProperty.js","qx:qx/core/MAssert.js","qx:qx/core/Object.js","qx:qx/util/DisposeUtil.js","qx:qx/event/type/Event.js","qx:qx/util/ObjectPool.js","qx:qx/event/Pool.js","qx:qx/event/dispatch/Direct.js","qx:qx/event/handler/Object.js","qx:qx/event/type/Data.js","qcl:qcl/application/MAppManagerProvider.js","qcl:qcl/application/MGetApplication.js","qx:qx/core/BaseInit.js","qx:qx/event/type/Native.js","qx:qx/event/handler/Window.js","qx:qx/event/handler/Application.js","qx:qx/core/Init.js","qcl:qcl/application/MWidgetId.js","qcl:qcl/application/SessionManager.js","qx:qx/event/message/Bus.js","qx:qx/event/message/Message.js","qcl:qcl/application/StateManager.js","qx:qx/bom/client/Event.js","qx:qx/bom/History.js","qx:qx/bom/HashHistory.js","qx:qx/event/handler/Iframe.js","qx:qx/bom/Iframe.js","qx:qx/lang/Object.js","qx:qx/dom/Element.js","qx:qx/bom/client/Html.js","qx:qx/bom/element/Attribute.js","qx:qx/dom/Hierarchy.js","qx:qx/util/LibraryManager.js","qx:qx/bom/client/Transport.js","qx:qx/util/ResourceManager.js","qx:qx/event/Timer.js","qx:qx/event/Idle.js","qx:qx/bom/IframeHistory.js","qx:qx/bom/NativeHistory.js","qcl:qcl/io/RpcManager.js","qx:qx/io/remote/Rpc.js","qx:qx/io/remote/Request.js","qx:qx/io/remote/RequestQueue.js","qx:qx/io/remote/Exchange.js","qx:qx/io/remote/transport/Abstract.js","qx:qx/io/remote/transport/Iframe.js","qx:qx/bom/element/Opacity.js","qx:qx/bom/element/Cursor.js","qx:qx/bom/element/BoxSizing.js","qx:qx/bom/element/Clip.js","qx:qx/bom/client/Css.js","qx:qx/bom/element/Style.js","qx:qx/bom/Document.js","qx:qx/bom/Viewport.js","qx:qx/io/remote/transport/Script.js","qx:qx/io/remote/transport/XmlHttp.js","qx:qx/bom/client/Plugin.js","qx:qx/xml/Document.js","qx:qx/bom/client/Xml.js","qx:qx/io/remote/Response.js","qcl:qcl/data/store/JsonRpc.js","qx:qx/data/marshal/IMarshaler.js","qx:qx/data/marshal/Json.js","qx:qx/data/marshal/MEventBubbling.js","qx:qx/util/PropertyUtil.js","qx:qx/data/Array.js","qx:qx/ui/core/MLayoutHandling.js","qx:qx/locale/MTranslation.js","qx:qx/ui/core/LayoutItem.js","qx:qx/theme/manager/Appearance.js","qx:qx/util/ValueManager.js","qx:qx/theme/manager/Color.js","qx:qx/util/ColorUtil.js","qx:qx/ui/core/queue/Layout.js","qx:qx/type/BaseArray.js","qx:qxWeb.js","qx:qx/bom/Selector.js","qx:qx/module/Css.js","qx:qx/bom/element/Class.js","qx:qx/bom/element/Dimension.js","qx:qx/bom/element/Location.js","qx:qx/bom/Stylesheet.js","qx:qx/util/Uri.js","qx:qx/bom/client/Stylesheet.js","qx:qx/module/Polyfill.js","qx:qx/module/Event.js","qx:qx/event/Emitter.js","qx:qx/module/Animation.js","qx:qx/bom/element/Animation.js","qx:qx/bom/client/CssAnimation.js","qx:qx/bom/element/AnimationCss.js","qx:qx/bom/element/AnimationHandle.js","qx:qx/bom/element/AnimationJs.js","qx:qx/bom/client/CssTransform.js","qx:qx/bom/element/Transform.js","qx:qx/bom/AnimationFrame.js","qx:qx/util/DeferredCallManager.js","qx:qx/util/DeferredCall.js","qx:qx/html/Element.js","qx:qx/event/handler/Appear.js","qx:qx/event/dispatch/AbstractBubbling.js","qx:qx/event/dispatch/DomBubbling.js","qx:qx/event/handler/Element.js","qx:qx/event/handler/UserAction.js","qx:qx/event/handler/Mouse.js","qx:qx/event/type/Dom.js","qx:qx/event/type/Touch.js","qx:qx/event/type/Tap.js","qx:qx/bom/client/Scroll.js","qx:qx/bom/element/Scroll.js","qx:qx/event/type/Swipe.js","qx:qx/event/handler/Orientation.js","qx:qx/event/type/Orientation.js","qx:qx/event/handler/TouchCore.js","qx:qx/event/handler/Touch.js","qx:qx/bom/client/Device.js","qx:qx/event/handler/MouseEmulation.js","qx:qx/event/type/Mouse.js","qx:qx/event/type/MouseWheel.js","qx:qx/event/handler/Keyboard.js","qx:qx/event/type/KeyInput.js","qx:qx/event/type/KeySequence.js","qx:qx/event/util/Keyboard.js","qx:qx/event/handler/Focus.js","qx:qx/bom/Selection.js","qx:qx/bom/Range.js","qx:qx/util/StringSplit.js","qx:qx/event/type/Focus.js","qx:qx/event/handler/Input.js","qx:qx/event/handler/Capture.js","qx:qx/event/handler/DragDrop.js","qx:qx/event/type/Drag.js","qx:qx/ui/core/MPlacement.js","qx:qx/util/placement/Placement.js","qx:qx/util/placement/AbstractAxis.js","qx:qx/util/placement/DirectAxis.js","qx:qx/util/placement/KeepAlignAxis.js","qx:qx/util/placement/BestFitAxis.js","qx:qx/ui/core/Widget.js","qx:qx/ui/core/EventHandler.js","qx:qx/ui/layout/Abstract.js","qx:qx/ui/core/queue/Visibility.js","qx:qx/ui/core/queue/Manager.js","qx:qx/ui/core/queue/Widget.js","qx:qx/ui/core/queue/Appearance.js","qx:qx/ui/core/queue/Dispose.js","qx:qx/theme/manager/Decoration.js","qx:qx/ui/style/Stylesheet.js","qx:qx/ui/decoration/MBackgroundColor.js","qx:qx/ui/decoration/IDecorator.js","qx:qx/ui/decoration/Abstract.js","qx:qx/ui/decoration/MBackgroundImage.js","qx:qx/util/AliasManager.js","qx:qx/ui/decoration/MSingleBorder.js","qx:qx/ui/decoration/MDoubleBorder.js","qx:qx/ui/decoration/MBorderRadius.js","qx:qx/ui/decoration/MBorderImage.js","qx:qx/ui/decoration/MLinearBackgroundGradient.js","qx:qx/ui/decoration/MBoxShadow.js","qx:qx/ui/decoration/Decorator.js","qx:qx/type/BaseString.js","qx:qx/locale/LocalizedString.js","qx:qx/locale/Manager.js","qx:qx/bom/client/Locale.js","qx:qx/ui/basic/Image.js","qx:qx/html/Image.js","qx:qx/bom/element/Decoration.js","qx:qx/io/ImageLoader.js","qx:qx/bom/element/Background.js","qx:qx/ui/core/DragDropCursor.js","qx:qx/event/handler/Offline.js","qx:qx/bom/Element.js","qx:qx/event/dispatch/MouseCapture.js","qx:qx/ui/core/MChildrenHandling.js","qx:qx/ui/container/Composite.js","dialog:dialog/Dialog.js","dialog:dialog/Alert.js","qx:qx/ui/core/MRemoteChildrenHandling.js","qx:qx/ui/core/MRemoteLayoutHandling.js","qx:qx/ui/form/MForm.js","qx:qx/ui/form/IForm.js","qx:qx/ui/core/MContentPadding.js","qx:qx/ui/groupbox/GroupBox.js","qx:qx/ui/layout/Canvas.js","qx:qx/ui/layout/Util.js","qx:qx/ui/basic/Atom.js","qx:qx/ui/layout/Atom.js","qx:qx/ui/form/IStringForm.js","qx:qx/ui/basic/Label.js","qx:qx/html/Label.js","qx:qx/bom/Label.js","qx:qx/theme/manager/Font.js","qx:qx/bom/Font.js","qx:qx/bom/webfonts/WebFont.js","qx:qx/bom/webfonts/Manager.js","qx:qx/bom/webfonts/Validator.js","qx:qx/ui/layout/VBox.js","qx:qx/ui/layout/HBox.js","dialog:dialog/Confirm.js","qx:qx/ui/core/MExecutable.js","qx:qx/ui/form/IExecutable.js","qx:qx/ui/form/Button.js","dialog:dialog/Prompt.js","qx:qx/ui/form/AbstractField.js","qx:qx/html/Input.js","qx:qx/bom/Input.js","qx:qx/ui/form/TextField.js","dialog:dialog/Select.js","qx:qx/ui/form/renderer/IFormRenderer.js","qx:qx/ui/form/renderer/AbstractRenderer.js","qx:qx/ui/form/renderer/Single.js","qx:qx/ui/layout/Grid.js","dialog:dialog/FormRenderer.js","qx:qx/ui/core/MSingleSelectionHandling.js","qx:qx/ui/core/SingleSelectionManager.js","qx:qx/ui/core/ISingleSelectionProvider.js","qx:qx/ui/form/MModelSelection.js","qx:qx/ui/core/ISingleSelection.js","qx:qx/ui/form/IModelSelection.js","qx:qx/ui/form/RadioGroup.js","qx:qx/ui/form/IRadioItem.js","qx:qx/ui/form/IBooleanForm.js","qx:qx/ui/form/ToggleButton.js","qx:qx/ui/form/MModelProperty.js","qx:qx/ui/form/IModel.js","qx:qx/ui/form/CheckBox.js","qx:qx/util/Validate.js","qx:qx/util/Serializer.js","dialog:dialog/Form.js","qx:qx/ui/layout/Grow.js","qx:qx/ui/form/Form.js","qx:qx/ui/form/validation/Manager.js","qx:qx/ui/form/validation/AsyncValidator.js","qx:qx/ui/form/Resetter.js","qx:qx/data/controller/ISelection.js","qx:qx/ui/form/IColorForm.js","qx:qx/ui/form/IDateForm.js","qx:qx/ui/form/INumberForm.js","qx:qx/data/controller/Object.js","qx:qx/ui/form/TextArea.js","qx:qx/ui/form/DateField.js","qx:qx/locale/Date.js","qx:qx/util/format/IFormat.js","qx:qx/util/format/DateFormat.js","qx:qx/ui/control/DateChooser.js","qx:qx/ui/popup/Popup.js","qx:qx/ui/popup/Manager.js","qx:qx/ui/tooltip/ToolTip.js","qx:qx/ui/toolbar/Button.js","qx:qx/ui/toolbar/PartContainer.js","qx:qx/ui/form/PasswordField.js","qx:qx/ui/form/AbstractSelectBox.js","qx:qx/ui/core/selection/Abstract.js","qx:qx/ui/core/selection/Widget.js","qx:qx/ui/core/selection/ScrollArea.js","qx:qx/ui/core/MMultiSelectionHandling.js","qx:qx/ui/core/IMultiSelection.js","qx:qx/ui/core/MDragDropScrolling.js","qx:qx/ui/core/scroll/MScrollBarFactory.js","qx:qx/ui/core/scroll/IScrollBar.js","qx:qx/ui/core/scroll/NativeScrollBar.js","qx:qx/ui/core/scroll/ScrollBar.js","qx:qx/ui/form/IRange.js","qx:qx/ui/form/Slider.js","qx:qx/ui/core/scroll/ScrollSlider.js","qx:qx/ui/form/RepeatButton.js","qx:qx/event/AcceleratingTimer.js","qx:qx/ui/core/scroll/MWheelHandling.js","qx:qx/ui/core/scroll/AbstractScrollArea.js","qx:qx/ui/core/scroll/ScrollPane.js","qx:qx/ui/form/List.js","qx:qx/bom/String.js","qx:qx/util/StringEscape.js","qx:qx/ui/form/ComboBox.js","qx:qx/ui/form/SelectBox.js","qx:qx/ui/core/Spacer.js","qx:qx/data/controller/MSelection.js","qx:qx/data/controller/List.js","qx:qx/ui/form/ListItem.js","qx:qx/ui/form/RadioButton.js","qx:qx/ui/core/FocusHandler.js","qx:qx/ui/core/Blocker.js","qx:qx/html/Blocker.js","qcl:qcl/access/AccessManager.js","qcl:qcl/access/AbstractManager.js","qcl:qcl/access/UserManager.js","qcl:qcl/access/User.js","qcl:qcl/access/PermissionManager.js","qcl:qcl/access/Permission.js","qcl:qcl/application/ConfigManager.js","qcl:qcl/application/PluginManager.js","qx:qx/bom/request/Script.js","qcl:qcl/ui/dialog/Dialog.js","qx:qx/application/IApplication.js","qx:qx/application/AbstractGui.js","qx:qx/theme/manager/Meta.js","qx:qx/theme/manager/Icon.js","qx:qx/Theme.js","qx:qx/ui/tooltip/Manager.js","qx:qx/application/Standalone.js","qx:qx/ui/window/MDesktop.js","qx:qx/ui/core/MBlocker.js","qx:qx/ui/root/Abstract.js","qx:qx/ui/root/Application.js","qx:qx/html/Root.js","qcl:qcl/ui/MLoadingPopup.js","qx:qx/ui/menu/AbstractButton.js","qx:qx/ui/menu/ButtonLayout.js","qx:qx/ui/menu/Menu.js","qx:qx/ui/menu/Layout.js","qx:qx/ui/menu/Separator.js","qx:qx/ui/menu/Manager.js","qx:qx/ui/form/MenuButton.js","qx:qx/ui/menubar/Button.js","qx:qx/ui/toolbar/ToolBar.js","qx:qx/ui/toolbar/Separator.js","qx:qx/ui/toolbar/Part.js","qx:qx/ui/menu/Button.js","qx:qx/ui/container/SlideBar.js","qx:qx/ui/menu/MenuSlideBar.js","qx:qx/ui/form/HoverButton.js","qx:qx/ui/menu/RadioButton.js","bibliograph:bibliograph/theme/Assets.js","bibliograph:bibliograph/Main.js","qx:qx/log/appender/Util.js","qx:qx/log/appender/Native.js","persist:persist/Store.js","bibliograph:bibliograph/Application.js","qx:qx/ui/window/IWindowManager.js","qx:qx/ui/window/IDesktop.js","qx:qx/ui/window/Manager.js","qx:qx/ui/core/MMovable.js","qx:qx/ui/core/MResizable.js","qx:qx/ui/window/Window.js","bibliograph:bibliograph/ui/window/DatasourceListWindow.js","bibliograph:bibliograph/ui/window/AccessControlTool.js","qx:qx/ui/tree/selection/SelectionManager.js","qx:qx/ui/tree/core/AbstractItem.js","qx:qx/ui/tree/core/FolderOpenButton.js","qx:qx/ui/tree/core/AbstractTreeItem.js","qx:qx/ui/tree/Tree.js","qx:qx/data/controller/Tree.js","qx:qx/ui/tree/TreeFolder.js","bibliograph:bibliograph/ui/window/FolderTreeWindow.js","bibliograph:bibliograph/ui/window/FolderTreeWindowUi.js","qcl:qcl/ui/treevirtual/TreeView.js","virtualdata:virtualdata/marshal/TreeVirtual.js","qx:qx/ui/table/ITableModel.js","qx:qx/ui/table/model/Abstract.js","virtualdata:virtualdata/model/SimpleTreeDataModel.js","qx:qx/ui/treevirtual/MTreePrimitive.js","qx:qx/ui/treevirtual/SimpleTreeDataModel.js","qx:qx/ui/table/selection/Model.js","qx:qx/ui/table/Table.js","qx:qx/ui/table/IRowRenderer.js","qx:qx/ui/table/rowrenderer/Default.js","qx:qx/ui/table/IColumnMenuButton.js","qx:qx/ui/table/columnmenu/Button.js","qx:qx/ui/table/IColumnMenuItem.js","qx:qx/ui/menu/CheckBox.js","qx:qx/ui/table/columnmenu/MenuItem.js","qx:qx/ui/table/selection/Manager.js","qx:qx/ui/table/ICellRenderer.js","qx:qx/ui/table/cellrenderer/Abstract.js","qx:qx/ui/table/cellrenderer/Default.js","qx:qx/util/format/NumberFormat.js","qx:qx/locale/Number.js","qx:qx/ui/table/ICellEditorFactory.js","qx:qx/ui/table/celleditor/AbstractField.js","qx:qx/ui/table/celleditor/TextField.js","qx:qx/ui/table/IHeaderRenderer.js","qx:qx/ui/table/headerrenderer/Default.js","qx:qx/ui/table/headerrenderer/HeaderCell.js","qx:qx/ui/table/columnmodel/Basic.js","qx:qx/ui/table/pane/Pane.js","qx:qx/ui/table/pane/Header.js","qx:qx/ui/table/pane/Scroller.js","qx:qx/ui/table/pane/Clipper.js","qx:qx/ui/table/pane/FocusIndicator.js","qx:qx/ui/table/pane/CellEvent.js","qx:qx/lang/Number.js","qx:qx/ui/table/pane/Model.js","qx:qx/ui/table/model/Simple.js","qx:qx/ui/treevirtual/TreeVirtual.js","qx:qx/ui/treevirtual/SimpleTreeDataCellRenderer.js","qx:qx/ui/treevirtual/DefaultDataCellRenderer.js","qx:qx/ui/treevirtual/SimpleTreeDataRowRenderer.js","qx:qx/ui/treevirtual/SelectionManager.js","qx:qx/ui/table/columnmodel/Resize.js","qx:qx/ui/core/ColumnData.js","qx:qx/ui/table/columnmodel/resizebehavior/Abstract.js","qx:qx/ui/table/columnmodel/resizebehavior/Default.js","virtualdata:virtualdata/controller/TreeVirtual.js","qx:qx/ui/treevirtual/MNode.js","qcl:qcl/ui/treevirtual/DragDropTree.js","bibliograph:bibliograph/ui/window/PreferencesWindow.js","qx:qx/ui/tabview/TabView.js","qx:qx/ui/container/Stack.js","qx:qx/ui/tabview/Page.js","qx:qx/ui/tabview/TabButton.js","bibliograph:bibliograph/ui/window/ImportWindow.js","uploadwidget:uploadwidget/UploadForm.js","uploadwidget:uploadwidget/UploadField.js","uploadwidget:uploadwidget/UploadButton.js","bibliograph:bibliograph/ui/window/ImportWindowUi.js","bibliograph:bibliograph/ui/reference/ListView.js","qx:qx/util/TimerManager.js","virtualdata:virtualdata/marshal/Table.js","virtualdata:virtualdata/controller/MLoadingPopup.js","virtualdata:virtualdata/controller/Table.js","qx:qx/ui/table/model/Remote.js","virtualdata:virtualdata/model/Table.js","bibliograph:bibliograph/ui/window/AboutWindow.js","bibliograph:bibliograph/ui/window/SearchHelpWindow.js","qx:qx/ui/core/MNativeOverflow.js","qx:qx/ui/embed/Html.js","bibliograph:bibliograph/ui/main/Toolbar.js","qx:qx/ui/toolbar/MenuButton.js","bibliograph:bibliograph/ui/main/FolderTreePanel.js","qx:qx/ui/menubar/MenuBar.js","bibliograph:bibliograph/ui/folder/TreeView.js","bibliograph:bibliograph/ui/folder/TreeViewUi.js","bibliograph:bibliograph/ui/main/ReferenceListView.js","bibliograph:bibliograph/ui/reference/ListViewUi.js","bibliograph:bibliograph/ui/main/ItemView.js","bibliograph:bibliograph/ui/main/ItemViewUi.js","bibliograph:bibliograph/ui/item/MCreateForm.js","qcl:qcl/data/controller/AutoComplete.js","bibliograph:bibliograph/ui/item/ReferenceEditor.js","qx:qx/data/controller/Form.js","bibliograph:bibliograph/ui/item/FormRenderer.js","bibliograph:bibliograph/ui/item/ReferenceEditorUi.js","bibliograph:bibliograph/ui/item/RecordInfo.js","bibliograph:bibliograph/ui/item/RecordInfoUi.js","qx:qx/ui/table/cellrenderer/AbstractImage.js","qx:qx/ui/table/cellrenderer/Image.js","bibliograph:bibliograph/ui/item/DuplicatesView.js","bibliograph:bibliograph/ui/item/DuplicatesViewUi.js","qx:qx/ui/container/Scroll.js","bibliograph:bibliograph/ui/item/TableView.js","bibliograph:bibliograph/ui/item/TableViewUi.js","dialog:dialog/Login.js","qx:qx/ui/splitpane/Pane.js","qx:qx/ui/splitpane/Slider.js","qx:qx/ui/splitpane/Splitter.js","qx:qx/ui/splitpane/Blocker.js","qx:qx/ui/splitpane/VLayout.js","qx:qx/ui/splitpane/HLayout.js","qx:qx/theme/modern/Color.js","bibliograph:bibliograph/theme/Color.js","qx:qx/theme/modern/Decoration.js","bibliograph:bibliograph/theme/Decoration.js","qx:qx/theme/icon/Tango.js","qx:qx/theme/modern/Appearance.js","bibliograph:bibliograph/theme/Appearance.js","qx:qx/theme/modern/Font.js","bibliograph:bibliograph/theme/Font.js","bibliograph:bibliograph/theme/Theme.js"]}},
  urisBefore : ["../build/resource/persist/persist-all-min.js"],
  cssBefore : [],
  boot : "boot",
  closureParts : {},
  bootIsInline : false,
  addNoCacheParam : false,

  decodeUris : function(compressedUris)
  {
    var libs = qx.$$libraries;
    var uris = [];
    for (var i=0; i<compressedUris.length; i++)
    {
      var uri = compressedUris[i].split(":");
      var euri;
      if (uri.length==2 && uri[0] in libs) {
        var prefix = libs[uri[0]].sourceUri;
        euri = prefix + "/" + uri[1];
      } else {
        euri = compressedUris[i];
      }
      if (qx.$$loader.addNoCacheParam) {
        euri += "?nocache=" + Math.random();
      }
      
      uris.push(euri);
    }
    return uris;
  }
};

var readyStateValue = {"complete" : true};
if (document.documentMode && document.documentMode < 10 ||
    (typeof window.ActiveXObject !== "undefined" && !document.documentMode)) {
  readyStateValue["loaded"] = true;
}

function loadScript(uri, callback) {
  var elem = document.createElement("script");
  elem.charset = "utf-8";
  elem.src = uri;
  elem.onreadystatechange = elem.onload = function() {
    if (!this.readyState || readyStateValue[this.readyState]) {
      elem.onreadystatechange = elem.onload = null;
      if (typeof callback === "function") {
        callback();
      }
    }
  };

  if (isLoadParallel) {
    elem.async = null;
  }

  var head = document.getElementsByTagName("head")[0];
  head.appendChild(elem);
}

function loadCss(uri) {
  var elem = document.createElement("link");
  elem.rel = "stylesheet";
  elem.type= "text/css";
  elem.href= uri;
  var head = document.getElementsByTagName("head")[0];
  head.appendChild(elem);
}

var isWebkit = /AppleWebKit\/([^ ]+)/.test(navigator.userAgent);
var isLoadParallel = 'async' in document.createElement('script');

function loadScriptList(list, callback) {
  if (list.length == 0) {
    callback();
    return;
  }

  var item;

  if (isLoadParallel) {
    while (list.length) {
      item = list.shift();
      if (list.length) {
        loadScript(item);
      } else {
        loadScript(item, callback);
      }
    }
  } else {
    item = list.shift();
    loadScript(item,  function() {
      if (isWebkit) {
        // force async, else Safari fails with a "maximum recursion depth exceeded"
        window.setTimeout(function() {
          loadScriptList(list, callback);
        }, 0);
      } else {
        loadScriptList(list, callback);
      }
    });
  }
}

var fireContentLoadedEvent = function() {
  qx.$$domReady = true;
  document.removeEventListener('DOMContentLoaded', fireContentLoadedEvent, false);
};
if (document.addEventListener) {
  document.addEventListener('DOMContentLoaded', fireContentLoadedEvent, false);
}

qx.$$loader.importPackageData = function (dataMap, callback) {
  if (dataMap["resources"]){
    var resMap = dataMap["resources"];
    for (var k in resMap) qx.$$resources[k] = resMap[k];
  }
  if (dataMap["locales"]){
    var locMap = dataMap["locales"];
    var qxlocs = qx.$$locales;
    for (var lang in locMap){
      if (!qxlocs[lang]) qxlocs[lang] = locMap[lang];
      else
        for (var k in locMap[lang]) qxlocs[lang][k] = locMap[lang][k];
    }
  }
  if (dataMap["translations"]){
    var trMap   = dataMap["translations"];
    var qxtrans = qx.$$translations;
    for (var lang in trMap){
      if (!qxtrans[lang]) qxtrans[lang] = trMap[lang];
      else
        for (var k in trMap[lang]) qxtrans[lang][k] = trMap[lang][k];
    }
  }
  if (callback){
    callback(dataMap);
  }
}

qx.$$loader.signalStartup = function ()
{
  qx.$$loader.scriptLoaded = true;
  if (window.qx && qx.event && qx.event.handler && qx.event.handler.Application) {
    qx.event.handler.Application.onScriptLoaded();
    qx.$$loader.applicationHandlerReady = true;
  } else {
    qx.$$loader.applicationHandlerReady = false;
  }
}

// Load all stuff
qx.$$loader.init = function(){
  var l=qx.$$loader;
  if (l.cssBefore.length>0) {
    for (var i=0, m=l.cssBefore.length; i<m; i++) {
      loadCss(l.cssBefore[i]);
    }
  }
  if (l.urisBefore.length>0){
    loadScriptList(l.urisBefore, function(){
      l.initUris();
    });
  } else {
    l.initUris();
  }
}

// Load qooxdoo boot stuff
qx.$$loader.initUris = function(){
  var l=qx.$$loader;
  var bootPackageHash=l.parts[l.boot][0];
  if (l.bootIsInline){
    l.importPackageData(qx.$$packageData[bootPackageHash]);
    l.signalStartup();
  } else {
    loadScriptList(l.decodeUris(l.packages[l.parts[l.boot][0]].uris), function(){
      // Opera needs this extra time to parse the scripts
      window.setTimeout(function(){
        l.importPackageData(qx.$$packageData[bootPackageHash] || {});
        l.signalStartup();
      }, 0);
    });
  }
}
})();



qx.$$loader.init();

