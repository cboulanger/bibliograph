(function(){

if (!window.qx) window.qx = {};

qx.$$start = new Date();

if (!qx.$$environment) qx.$$environment = {};
var envinfo = {"qx.application":"bibliograph.Application","qx.revision":"","qx.theme":"bibliograph.theme.Theme","qx.version":"3.5"};
for (var k in envinfo) qx.$$environment[k] = envinfo[k];

if (!qx.$$libraries) qx.$$libraries = {};
var libinfo = {"__out__":{"sourceUri":"script"},"bibliograph":{"resourceUri":"../source/resource","sourceUri":"../source/class"},"dialog":{"resourceUri":"../contrib/Dialog/master/source/resource","sourceUri":"../contrib/Dialog/master/source/class"},"persist":{"resourceUri":"../contrib/Persist/trunk/source/resource","sourceUri":"../contrib/Persist/trunk/source/class"},"qcl":{"resourceUri":"../contrib/qcl/0.2/source/resource","sourceUri":"../contrib/qcl/0.2/source/class"},"qx":{"resourceUri":"../../qooxdoo/qooxdoo-3.5-sdk/framework/source/resource","sourceUri":"../../qooxdoo/qooxdoo-3.5-sdk/framework/source/class","sourceViewUri":"https://github.com/qooxdoo/qooxdoo/blob/%{qxGitBranch}/framework/source/class/%{classFilePath}#L%{lineNumber}"},"uploadwidget":{"resourceUri":"../contrib/UploadWidget/trunk/source/resource","sourceUri":"../contrib/UploadWidget/trunk/source/class"},"virtualdata":{"resourceUri":"../contrib/VirtualData/trunk/source/resource","sourceUri":"../contrib/VirtualData/trunk/source/class"}};
for (var k in libinfo) qx.$$libraries[k] = libinfo[k];

qx.$$resources = {};
qx.$$translations = {"C":null,"de":null,"en":null};
qx.$$locales = {"C":null,"de":null,"en":null};
qx.$$packageData = {};
qx.$$g = {}

qx.$$loader = {
  parts : {"boot":[0]},
  packages : {"0":{"uris":["__out__:bibliograph.1e34734e5ce5.js","bibliograph:bibliograph/theme/Assets.js","bibliograph:bibliograph/Main.js","__out__:bibliograph.b6dde3100d33.js","bibliograph:bibliograph/Application.js","__out__:bibliograph.ba52a3673eae.js","bibliograph:bibliograph/ui/window/DatasourceListWindow.js","bibliograph:bibliograph/ui/window/AccessControlTool.js","__out__:bibliograph.ab3118652fae.js","bibliograph:bibliograph/ui/window/FolderTreeWindow.js","bibliograph:bibliograph/ui/window/FolderTreeWindowUi.js","__out__:bibliograph.0b7e60ff91d5.js","bibliograph:bibliograph/ui/window/PreferencesWindow.js","__out__:bibliograph.3d1dcb5a0e52.js","bibliograph:bibliograph/ui/window/ImportWindow.js","__out__:bibliograph.01d4fcdc6860.js","bibliograph:bibliograph/ui/window/ImportWindowUi.js","bibliograph:bibliograph/ui/reference/ListView.js","__out__:bibliograph.2f7ae4dd04d2.js","bibliograph:bibliograph/ui/window/AboutWindow.js","bibliograph:bibliograph/ui/window/SearchHelpWindow.js","__out__:bibliograph.7d654e2df978.js","bibliograph:bibliograph/ui/main/Toolbar.js","__out__:bibliograph.927dbbcbfe63.js","bibliograph:bibliograph/ui/main/FolderTreePanel.js","__out__:bibliograph.45dc7bec4af7.js","bibliograph:bibliograph/ui/folder/TreeView.js","bibliograph:bibliograph/ui/folder/TreeViewUi.js","bibliograph:bibliograph/ui/main/ReferenceListView.js","bibliograph:bibliograph/ui/reference/ListViewUi.js","bibliograph:bibliograph/ui/main/ItemView.js","bibliograph:bibliograph/ui/main/ItemViewUi.js","bibliograph:bibliograph/ui/item/MCreateForm.js","__out__:bibliograph.c2c3b2db8fd8.js","bibliograph:bibliograph/ui/item/ReferenceEditor.js","__out__:bibliograph.6a33b5f287cd.js","bibliograph:bibliograph/ui/item/FormRenderer.js","bibliograph:bibliograph/ui/item/ReferenceEditorUi.js","bibliograph:bibliograph/ui/item/RecordInfo.js","bibliograph:bibliograph/ui/item/RecordInfoUi.js","__out__:bibliograph.cfb80be3ef90.js","bibliograph:bibliograph/ui/item/DuplicatesView.js","bibliograph:bibliograph/ui/item/DuplicatesViewUi.js","__out__:bibliograph.7e93dcc72fde.js","bibliograph:bibliograph/ui/item/TableView.js","bibliograph:bibliograph/ui/item/TableViewUi.js","__out__:bibliograph.f565bf8e831d.js","bibliograph:bibliograph/theme/Color.js","__out__:bibliograph.870138203df5.js","bibliograph:bibliograph/theme/Decoration.js","__out__:bibliograph.8f137a1bf1fe.js","bibliograph:bibliograph/theme/Appearance.js","__out__:bibliograph.38c4a3d0a696.js","bibliograph:bibliograph/theme/Font.js","bibliograph:bibliograph/theme/Theme.js"]}},
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

