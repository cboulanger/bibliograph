Release notes
=============

Version 2.3 (October 2017)
-----------------------
- added Debugging Plugin with a server log window, log filter selection and recording of 
  JSONRPC traffic for later replay.
- Added Travis CI build script and tests

Version 2.2 (July 2017)
-----------------------
- Integrated fixes from Gerrit Oldenburg (Humboldt University Berlin) for 
  compatibility with PHP 7 and newer MySQL versions
- Upgrade to qoodoo 5.0.2
- Partial rewrite of the z3950 plugin, now uses a ServerProgress widget to indicate
  the progress of the search request
- Removed the annoying "No connection to server" alert if a server request fails

Version 2.1 (Mid-2015)
-----------------------
A [large number](https://github.com/cboulanger/bibliograph/issues?q=milestone%3Av2.1+is%3Aclosed)
of changes, fixes, improvements and refactoring under the hood.
- Upgraded to qooxdoo 4 on the frontend and to PHP > 5.3. on the backend
- Clean-up and refactoring of the backend and the qcl library. Fixed various problems 
  related to session management.
- New plugin architecture: plugins now live in a separate folder, with backend and
  frontend code together, as qooxdoo library
- New plugin 'backup': php-only backup solution, old code based on mysqldump moved
  to plugin 'mdbackup' (deprecated and no longer supported) 
- New plugin 'nnforum': Integrate user forum
- New plugin 'isbnscanner': Import references by ISBN using a barcode scanner (experimental)
- New plugin 'rssfolder': export folders as RSS feeds and import from those feeds (experimental)
- Updated CiteProc engine
- Improved setup process
- Default is now to send passwords encrypted (hashed with the stored and a random salt), this
  can be changed to sending plaintext passwords (for example, for LDAP servers), but only if
  a https connection exists

Version 2.0 (2011)
------------------
- Based on qooxdoo 1.6 (having used every version from qoodoo 0.5 upwards)
- Was never publicly released, but used in production at Humboldt University's Law School
- Used its own backend library (qcl) and an xml to javascript compiler (qxtransformer).
- Moved core features to plugins: Bibutils, CSL, Z39.50 import, Backup

Version 1.0 (~2006)
-------------------
- Used the NetWindows javascript library (by Alex Russel)
- Was used in a criminological research project

Version 0.1 (~2003)
-------------------
- Was a single long PHP file.