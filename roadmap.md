Version 2.2
===========

Frontend
--------

- Replace persist.js library with qooxdoo localStorage Wrapper
- move plugins out of bibliograph namespace into separate hierarchy containing frontend & backend code

Backend
-------

- qcl: implement Token (distributable session id)
- qcl: Remove unnecessary Exceptions, reduce to a basic set
- qcl: Replace qcl_import with Autoloader
- convert project to use PHP namespaces
- replace defines with const
- move authors & keywords to own tables to allow metadata connections