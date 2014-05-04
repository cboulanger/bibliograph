Todo
====

Frontend
--------

- Metadata is not updated when changing reference id
- Replace persist.js library with qooxdoo localStorage Wrapper

Backend
-------

- QCL_APPLICATION_MODE = ("development", "production", "maintenance"):
  - dev = allow modification of models
  - prod = no modification
  - maintenance = temp. develop mode on production servers
- Token (pre-created session) must replace on-the-fly session generation ("S_", "P_")