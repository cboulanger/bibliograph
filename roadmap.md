Version 2.2
===========
This version will contain backwards-incompatible changes to the database and the plugin architecture.


Features:
---------
- move authors & keywords to own tables to allow metadata connections

Frontent/Backend
----------------
- move plugins out of bibliograph namespace into separate hierarchy containing frontend & backend code

Frontend
--------
- Replace persist.js library with qooxdoo localStorage Wrapper

Backend
-------
- qcl: implement Token (distributable session id)
- qcl: Remove unnecessary Exceptions, reduce to a basic set
- qcl: Overhaul error handling by backend in qcl/lib/rpcphp/server/JsonRpcServer.php
- qcl: Replace qcl_import with Autoloader
- convert project to use PHP namespaces
- replace defines with const where appropriate
- Cleanup: Remove all code problems resulting in currently suppressed E_STRICT & E_NOTICE errors

