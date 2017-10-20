# Bibliograph: Open Source Online Citation & Library Management

Master (PHP5): [![Build Status](https://travis-ci.org/cboulanger/bibliograph.svg?branch=master)](https://travis-ci.org/cboulanger/bibliograph) | PHP7 [![Build Status](https://travis-ci.org/cboulanger/bibliograph.svg?branch=branch_php7)](https://travis-ci.org/cboulanger/bibliograph)

[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=panyasan&url=https://github.com/cboulanger/bibliograph&title=Bibliograph&language=javascript&tags=github&category=software)

Bibliograph is a powerful open source web application for the collaborative
collection, editing and publishing of bibliographic data.

- [Demo installation](http://demo.bibliograph.org)
- [End User Documentation](http://help.bibliograph.org)
- [Download](http://sourceforge.net/projects/bibliograph/files/latest/download)
- [Installation](doc/install.md)
- [Release Notes](release-notes.md)

Bibliograph

- is an application that lets you collect, edit, and publish bibliographic data 
  collaboratively on the web;
- has a modern and intuitive user interface that makes the daily life of working 
  with bibliographies and library collections easy and fun;
- allows researchers, librarians, teachers and students work together online 
  without having to install software locally;
- is fully open source and free to download, install, use and adapt to your 
  particular need.

Bibliograph can be used by

- scholars and librarians who want to publish a library collection or a 
  thematic bibliography online;
- groups of researchers who work together in a research project and want to 
  collect and share bibliographic references;
- professors and teachers who want to share bibliographic information with their
  students.

## Features
- Organize bibliographic records in static folders or dynamic collections based 
  on queries;
- Rich metadata, Autocompletion and duplicate detection;
- Allows natural language queries like "title contains hamlet and author 
  beginswith shake";
- Fine-grained access control system with users, roles, groups and permissions 
  allows flexible user management and contol of who is allowed to view, enter, 
  edit and delete data;
- Unlimited amount of separate databases;
- Imports data from library catalogues (through Z39.50 interface), from 
  various file-based data formats (RIS, BibTeX, Endnote, MODS, and more), and
  from RSS feeds.
- Export into various open formats and publish folders as RSS feeds;
- Formats bibliographic records with various citation styles (APA, Chicago, ...) 
  using CSL templates and the citeproc style processor (http://citationstyles.org);
- Ability to create and restore snapshot backups of individual databases;
- LDAP integration to connect to existing LDAP servers;
- Optionally provides a user forum;
- Fully open source, can be easily adapted and extended by plugins. 

## Plugins
Bibliograph implements most advanced features through plugins. For a list of
Plugins, see [here](doc/plugins.md).

## Installation and Deployment
See [here](doc/install.md).

## Support
- See the extensive [end user online documentation](http://help.bibliograph.org).
- For general questions, please write to info at bibliograph dot org or send 
  a tweet to @bibliograph2.
- Bugs and feature requests should be registered as [github issues](https://github.com/cboulanger/bibliograph/issues).
- Paid support for installation or hosting is available, also if you need a plugin
  to support your particular collection.

## Development & Roadmap
- You can [hack the code](doc/development.md) and make it better;
- The current roadmap is [here](doc/roadmap.md);
- If you wish to sponsor a feature, please contact info at bibliograph dot org.

## How to contribute
Bibliograph is free (as in beer and in speech). But in order to thrive, the
project needs your help. Even if you are not a developer, you can contribute:
- You can [provide feedback, report bugs and/or suggest new features](https://github.com/cboulanger/bibliograph/issues).
- Help translate the user interface into your language. Let me know if you 
  are willing to do this, and I'll let you know how to do this.
- You can help spread the word. The more people use the application, the more
  likely it is that development continues and new features/plugins will be 
  added - so please let your followers on Twitter, Facebook etc. know about
  Bibliograph
- [Flattr me](https://flattr.com/submit/auto?user_id=panyasan&url=https://github.com/cboulanger/bibliograph&title=Bibliograph&language=javascript&tags=github&category=software)  
- You can also [donate](http://sourceforge.net/p/bibliograph/donate) and make sure 
  development continues

## Credits
Open source libraries/applications
- [qooxdoo JavaScript framework](http://www.qooxdoo.org): (c) 1&1 Internet AG 
- [CSL - The Citation Style Language](http://www.citationstyles.org).
- [CiteProc-PHP](https://bitbucket.org/rjerome/citeproc-php/) by Ron Jerome
- [CQL/SRU parser](https://github.com/simar0at/sru-cql-parser) by Robert Sanderson and Omar Siam
- [NoNonsense Forum](http://camendesign.com/nononsense_forum) (CC-BY) Kroc Camen 2010-2015 

Partial funding was provided by
- [Department of Law, Humboldt-Universität zu Berlin](http://www.rewi.hu-berlin.de)
- [Organized Crime Research Project](http://www.organized-crime.de/) (Version 1.0)

Bibliograph is developed using
- [Cloud9 IDE](http://c9.io) (Cloud-based coding and testing environment)
- [GitHub](http://github.com) (Version control and code hosting)
- [BrowserStack](http://browserstack.com) (Browser testing service - sponsored Open Source license)

In particular, the author wishes to thank:
- Gerrit Oldenburg (Humboldt Universität zu Berlin) for finding and fixing various
  bugs and providing preliminary PHP7-compatibility, and for supporting and
  maintaining the software at Humboldt Universität.
- Serge Barysiuk for providing assistance with UI generation, and for designing
  the application logo;
- Julika Rosenstock for writing the first version of the end user documentation, 
  Till Rathschlag and Anna Luetkefend for expanding and translating it. 
