Plugins
=======

Bibliograph implements most advanced features through plugins. The following
plugins are currently supported:
- [backup](https://github.com/cboulanger/bibliograph/tree/master/bibliograph/plugins/backup): Administrators and managers can initiate backups of 
  individual databases, and restore, download and delete backups (installed by default).
- [bibutils](https://github.com/cboulanger/bibliograph/tree/master/bibliograph/plugins/bibutils): Advanced export/import options via the Bibutils
  format conversion library.
- [csl](https://github.com/cboulanger/bibliograph/tree/master/bibliograph/plugins/csl): Format bibliographic data with the Citation Style Language 
  (installed by default)
- [nnforum](https://github.com/cboulanger/bibliograph/tree/master/bibliograph/plugins/nnforum): A user forum plugin that allows the site admin to 
  answer questions or the users to discuss issues related to the particular installation.
- [z3950](https://github.com/cboulanger/bibliograph/tree/master/bibliograph/plugins/z3950): Provides import from library catalogs which support 
  the Z39.50 interface. 

The following plugins are still under development and not supported:
- [isbnscanner](https://github.com/cboulanger/bibliograph/tree/master/bibliograph/plugins/isbnscanner): Import books with a ISBN scanner device
- [rssfolder](https://github.com/cboulanger/bibliograph/tree/master/bibliograph/plugins/rssfolder): Publish selected folders as RSS Feeds and 
  import from those feeds

You can easily add your own plugin:
- Execute `./generate.py create-plugin` in the "bibliograph" folder and follow the
  instructions.
- The easiest was to write a plugin is to reuse code from other plugins. The 
  "backup" plugin can be used as a model on how to write backend and
  frontend plugin code. The "fieldsExtensionExmpl" template shows how to extend
  the fields of the reference records.

When writing a plugin, you can add elements to existing widgets such as menu 
items or tab pages. Here is a list of widget ids that allow you to get the 
parent widget ( `this.getApplication().getWidgetById("XXX").add( myWidget );`)
- bibliograph/datasourceWindow
- bibliograph/accessControlTool
- bibliograph/folderTreeWindow
- bibliograph/preferencesWindow
- bibliograph/importWindow
- bibliograph/aboutWindow
- bibliograph/searchHelpWindow
- bibliograph/folder-settings-menu
- bibliograph/itemView
- bibliograph/referenceEditorStackView
- bibliograph/mainFolderTree
- bibliograph/referenceEditor
- bibliograph/mainListView
- bibliograph/toolbar
- bibliograph/datasourceButton
- bibliograph/menu-system
- bibliograph/importMenu
- application.helpMenu -> bibliograph/helpMenu
- applicationTitleLabel -> bibliograph/datasource-name
- rightList -> bibliograph/acltool-rightList
- bibliograph.preferences.tabView -> bibliograph/preferences-tabView
- 


