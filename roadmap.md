Sponsor a feature
=================
If you need a particular feature, you can hire me to delevop it. Write to
info at bibliograph dot org. Possible features are:
- a mobile version
- a zotero.org backend
- a library book checkout system
- sync with desktop reference managers
 
and much more. I'll be happy to develop a plugin for your particular need.

Features/changes that might be implemented or not, depending on
my free time & sufficient interest:

Version 2.2
===========
(will be moved into github issues)

Major new features
------------------
- add a NodeJS plugin for realtime messaging, the current PHP solution just #
  doesn't cut it.
- move authors & keywords to own tables to allow metadata connections

Technical stuff 
---------------
- Replace persist.js library with qooxdoo localStorage Wrapper
- qcl: refactor qcl_data_model_db_QueryBehavior to deal with rowCount problems
- qcl: Remove unnecessary Exceptions, reduce to a basic set
- qcl: Integrate qcl/lib/rpcphp/server/JsonRpcServer.php and tidy up
- qcl: Overhaul error handling by backend 
- qcl: Replace qcl_import with Autoloader
- qcl: Rename qcl_assert* functions
- qcl: implement Token (distributable session id)
- convert project to use PHP namespaces
- replace defines with const where appropriate
- Cleanup: Remove all code problems resulting in currently suppressed E_STRICT &
  E_NOTICE errors