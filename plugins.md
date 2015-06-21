Plugins
=======

Bibliograph implements most advanced features through plugins. The following
plugins are activated and supported:
- CSL: Format bibliographic data with the Citation Style Language (no dependencies, installed
  by default)
- Backup: Administrators and managers can initiate backups of individual databases, and restore,
  download and delete backups (no dependencies, installed by default).
- Z3950: Import from library catalogs which support the Z39.50 interface. Requires the PHP YAZ
  extension.
- Bibutils: Advanced export/import options via the Bibutils format conversion library (needs to
  be installed).
- NNForum: A user forum plugin that allows the site admin to answer questions or the users
  to discuss issues related to the particular installation.
- ISBNScanner: Import with a ISBN scanner device (experimental)
- RSS Feeds: Publish your folders as RSS Feeds and import from those feeds (Experimental) 

More plugins are under development. You can easily add your own plugin:
- Execute `./generate.py create-plugin` in the
  "bibliograph" folder and read the output that contains more information on how to
  proceed. The "backup" plugin can be used as a model on how to write backend and
  fronend plugin code.