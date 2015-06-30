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

The following plugins are still under development:
- [isbnscanner](https://github.com/cboulanger/bibliograph/tree/master/bibliograph/plugins/isbnscanner): Import books with a ISBN scanner device
- [rssfolder](https://github.com/cboulanger/bibliograph/tree/master/bibliograph/plugins/rssfolder): Publish selected folders as RSS Feeds and 
  import from those feeds

Information on writing new plugins is [here](development.md).
