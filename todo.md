Todo
====

Frontend
--------

- Metadata is not updated when changing reference id
- 

Backend
-------

- QCL_APPLICATION_MODE = ("development", "production", "maintenance"):
  - dev = allow modification of models
  - prod = no modification
  - maintenance = temp. develop mode on production servers
- qcl: Token (pre-created session) must replace on-the-fly session generation ("S_", "P_")
- Remove unnecessary Exceptions, reduce to a basic set