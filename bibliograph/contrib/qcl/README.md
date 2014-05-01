qcl: qooxdoo component library
==============================

qooxdoo component library: Qcl is a set of interoperating client-server JavaScript and PHP
libraries that are useful for web application development. The qooxdoo-style frontend library
provides common functionality like authentication, application configuration and widget
synchronization for the client application. The PHP backend extends the PHP JSONRPC server,
implementing the following technologies for PHP:

- Object and models with properties that behave similar to the qooxdoo property system.
- Controller-Model architecture (the data is sent to the frontend for the "View" part of the MVC design pattern)
- Object Persistence
- Active Record Pattern / Object-Relational Mapping(ORM) including automatic property
  management and fully automatic relational database table setup and update (MySql). DB tables are
  automagically synchronized with the property and relation schema.
- Fully abstracted table associations: Models are linked programmatically - no "JOIN" queries
  are necessary.
- Interacting with the user through dialogs initiated from the server (reversing the request-
  response model)

More here: http://qooxdoo.org/contrib/project/qcl/qcl_elements