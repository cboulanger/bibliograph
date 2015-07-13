Release notes
=============

Version 2.1 (Mid-2015)
-----------------------
A large number of changes, fixes, improvements and refactoring under the hood,
while staying backwards-compatible for database data and plugin code.
- upgraded to qooxdoo 4 on the frontend and to PHP > 5.3. on the backend
- clean-up and refactoring of the backend and the qcl library. Fixed various problems 
  related to session management.
- new plugin architecture: plugins now live in a separate folder, with backend and
  frontend code together, as qooxdoo library
- new plugin 'backup': php-only backup solution, old code based on mysqldump moved
  to plugin 'mdbackup' (deprecated and no longer supported) 
- new plugin 'nnforum': Integrate user forum
- new plugin 'isbnscanner': Import references by ISBN using a barcode scanner (experimental)
- new plugin 'rssfolder': export folders as RSS feeds and import from those feeds (experimental)
- updated CiteProc engine
- improved setup process
- Default is now to send passwords encrypted (hashed with the stored and a random salt), this
  can be changed to sending plaintext passwords (for example, for LDAP servers), but only if
  a https connection exists

Version 2.0 (2011)
------------------
- based on qooxdoo 1.6 (having used every version from qoodoo 0.5 upwards)
- was never publicly released, but used in production at Humboldt University's Law School
- used its own backend library (qcl) and an xml to javascript compiler (qxtransformer).
- moved core features to plugins: Bibutils, CSL, Z39.50 import, Backup

Version 1.0 (~2006)
-------------------
- used the NetWindows javascript library (a project by Alex Russel, who afterwards co-founded the Dojo toolkit)
- was used in a criminological research project

Version 0.1 (~2003)
-------------------
- was a single long PHP file.