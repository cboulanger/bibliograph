Development
===========

This is open source software, everybody is invited to hack on the code and help 
make it better! Bug fixes and new plugins are very welcome.

- Get the code by cloning it from git@github.com:cboulanger/bibliograph.git 
  (Most easily, by cloning it at GitHub itself).
- Building the application requires the qooxdoo library (currently, v4):
    * Download the latest 4.x SDK from
      http://sourceforge.net/projects/qooxdoo/files/qooxdoo-current/
    * Unzip the SDK into a top-level "qooxdoo" folder. You can also adapt the path
      to the SDK in the `bibliograph/config.json` configuration file if you don't
      want to store it there. For development, the location of the SDK files must
      be accessible to the web server.
    * Excute `./generate build` in the "bibliograph" folder.

- For deployment, you need to copy the bibliograph/build, bibliograph/plugins and
  bibliograph/services folders to the production server. The rest is only 
  necessary to build the application.
- Bibliograph features an extensible data model which allows easy modification of
  record fields and integration of a variety of backends (e.g., NoSql, xml, REST or
  even binary backends such as IMAP).

Writing new plugins
-------------------

You can easily add your own plugin:
- Execute `./generate.py create-plugin` in the "bibliograph" folder and follow the
  instructions.
- The easiest was to write a plugin is to reuse code from other plugins. The 
  "backup" plugin can be used as a model on how to write backend and
  frontend plugin code. The "fieldsExtensionExmpl" template shows how to extend
  the fields of the reference records.

When writing a plugin, you can add elements to existing widgets such as menu 
items or tab pages. Here is a list of widget ids that gives you access to 
the widgets of the core application by `this.getApplication().getWidgetById("XXX")`:

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
- bibliograph/helpMenu
- bibliograph/datasource-name
- bibliograph/acltool-rightList
- bibliograph/preferences-tabView
- bibliograph/loginDialog

You can search for these ids in the source code to see what they do. 

Translation
-----------

Bibliograph can be easily translated into any language. As it is written 
in JavaScript on the client and PHP on the server, there are separate 
translation files for client and server. If you are willing to translate
the existing messages into your language, let me know and I'll add the
locale files to the code. 