Bibliograph: Open Source Online Citation & Library Management
=============================================================

Bibliograph is a powerful open source web application for the collaborative
collection, editing and publishing of bibliographic data.

- [Demo installation](http://demo.bibliograph.org)
- [Documentation](http://help.bibliograph.org)
- [Download](http://sourceforge.net/projects/bibliograph/files/latest/download)
- [Release Notes](release-notes.md)
- [User Forum](http://forum.bibliograph.org)
- [Donate to the project](http://sourceforge.net/p/bibliograph/donate)

Bibliograph

- is an application that lets you collect, edit, and publish bibliographic data 
  collaboratively on the web.
- has a modern and intuitive user interface that makes the daily life of working 
  with bibliographies and library collections easy and fun.
- allows researchers, librarians, teachers and students work together online 
  without having to install software locally.
- is fully open source and free to download, install, use and adapt to your 
  particular need.

Bibliograph can be used by

- scholars and librarians who want to publish a library collection or a 
  thematic bibliography online
- groups of researchers who work together in a research project and want to 
  collect and share bibliographic references
- professors and teachers who want to share bibliographic information with their
  students

Features
--------
- Organize bibliographic records in static folders or dynamic collections based 
  on queries
- Rich metadata, Autocompletion and duplicate detection
- Allows natural language queries like "title contains hamlet and author 
  beginswith shake"
- Fine-grained access control system with users, roles, groups and permissions 
  allows flexible user management and contol of who is allowed to view, enter, 
  edit and delete data.
- Unlimited amount of separate databases
- Imports data from library catalogues (through Z39.50 interface) and from 
  various file-based data formats (RIS, BibTeX, Endnote, MODS, and more)
- Export into/import from open formats and publish folders as RSS feeds, import
  from those feeds
- Formats bibliographic records with various citation styles (APA, Chicago, ...) 
  using CSL templates and the citeproc style processor (http://citationstyles.org)
- Can create and restore snapshot backups of individual databases 
- LDAP integration to connect to existing LDAP servers
- Optionally provides a user forum
- Fully open source, can be easily adapted and extended

Plugins
-------
Bibliograph implements most advanced features through plugins. For a list of
Plugins, see [here](plugins.md).

Installation and Deployment
---------------------------
See [here](install.md).

Support
-------
- See the extensive [end user online documentation](http://help.bibliograph.org). 
- For general questions, please write to info at bibliograph dot org or send 
  a tweet to @bibliograph2.
- There is also a [user forum](forum.bibliograph.org) to discuss issues with other users.
- Bugs and feature requests should be registered as github issues:
  https://github.com/cboulanger/bibliograph/issues
- Paid support for installation or hosting is available, also if you need a plugin
  to support your particular collection.

Development & Roadmap
---------------------
- You can [hack the code](development.md) and make it better
- The current roadmap is [here](roadmap.md)
- If you wish to sponsor a feature, please contact info at bibliograph dot org

I am not a developer. How can I contribute to the project?
----------------------------------------------------------
- You can [donate](http://sourceforge.net/p/bibliograph/donate) and make sure 
  development continues.
- You can help spread the word. The more people use the application, the more
  likely it is that development continues and new features/plugins will be 
  added.
- You can provide feedback and suggest changes or features.

Credits
--------
Open source libraries/applications
- qooxdoo JavaScript framework: (c) 1&1 Internet AG 
  http://www.qooxdoo.org
- CSL - The Citation Style Language.
  http://www.citationstyles.org
- CiteProc-PHP. Author:  Ron Jerome
  https://bitbucket.org/rjerome/citeproc-php/
- CQL/SRU parser. Authors: Robert Sanderson, Omar Siam
  https://github.com/simar0at/sru-cql-parser
- NoNonsense Forum (CC-BY) Kroc Camen 2010-2015 
  http://camendesign.com/nononsense_forum

Partial funding was provided by
- Juristische Fakultät (Department of Law), Humboldt-Universität zu Berlin
  http://www.rewi.hu-berlin.de
- Organized Crime Research Project, Dr. Klaus von Lampe
  http://www.organized-crime.de/

The author wishes to thank:
- Serge Barysiuk for providing assistance with UI generation, and for designing
  the application logo
- Julika Rosenstock for writing the first version of the end user documentation. 
