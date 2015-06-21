Development
===========

This is open source software, everybody is invited to hack on the code and help 
make it better! Bug fixes and new plugins are very welcome.

- Get the code by cloning it from git@github.com:cboulanger/bibliograph.git 
  (Most easily, by cloning it at GitHub itself).
- Building the application requires the qooxdoo library (currently, v4):
    * Download the latest 4.x SDK from
      http://sourceforge.net/projects/qooxdoo/files/qooxdoo-current/
    * Unzip the SDK into a top-level "qooxdoo" folder. You can also adapt the path
      to the SDK in the `bibliograph/config.json` configuration file if you don't
      want to store it there. For development, the location of the SDK files must
      be accessible to the web server.
    * Excute `./generate build` in the "bibliograph" folder.

- For deployment, you need to copy the bibliograph/build, bibliograph/plugins and
  bibliograph/services folders to the production server. The rest is only 
  necessary to build the application.
- Bibliograph features an extensible data model which allows easy modification of
  record fields and integration of a variety of backends (e.g., NoSql, xml, REST or
  even binary backends such as IMAP).