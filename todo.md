Todo
====

Frontend
--------

- Bug: Metadata is not updated when changing reference id
- NTH: Replace persist.js library with qooxdoo localStorage Wrapper

Backend
-------

- Bug: Deleting reference doesn't move it into trash
- Bug: QCL_APPLICATION_MODE = ("development", "production", "maintenance"):
  - dev = allow modification of models
  - prod = no modification
  - maintenance = temp. develop mode on production servers
- Bug: qcl: Token (pre-created session) must replace on-the-fly session generation ("S_", "P_")
- NTH: Remove unnecessary Exceptions, reduce to a basic set
