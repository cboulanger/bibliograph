# Release notes

## Version 3.0.0 (currently in beta)
Features:
- New Plugin to support Zotero databases (read-only)
- Removed experimental plugins that have not been used much
Backend/Development
- Completely rewrote server part, replacing homegrown framework with Yii2-based backend
- Upgraded client to qooxdoo 6.0.0 and its new javascript compiler toolchain
- Added extensive test suite based on the Codeception testing framework
- CI moved from Travis to GitHub Actions, powerful deployment automation
- Added Browser-based tests based on Microsoft Playwright  

## Version 2.3 (October 2017)
- added Debugging plugin with a server log window, log filter selection and recording of 
  JSONRPC traffic for later replay.
- Added Travis CI build script and tests
- Support for easy deployment in Docker, Cloud9IDE and Debian/Ubuntu
- More compatibility fixes for current PHP & MySql versions

## Version 2.2 (July 2017)
- Integrated fixes from Gerrit Oldenburg (Humboldt University Berlin) for 
  upcoming compatibility with PHP 7 and newer MySQL versions (not yet functional)
- Upgrade to qoodoo 5.0.2
- Partial rewrite of the z3950 plugin, now uses a ServerProgress widget to indicate
  the progress of the search request
- Removed the annoying "No connection to server" alert if a server request fails

## Version 2.1 (Mid-2015)
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

## Version 2.0 (2011)
- Based on qooxdoo 1.6 (having used every version from qoodoo 0.5 upwards)
- Was never publicly released, but used in production at Humboldt University's Law School
- Used its own backend library (qcl) and an xml to javascript compiler (qxtransformer).
- Moved core features to plugins: Bibutils, CSL, Z39.50 import, Backup

## Version 1.0 (~2006)
- Used the NetWindows javascript library (by Alex Russel)
- Was used in a criminological research project

## Version 0.1 (~2003)
- A single long PHP file.
