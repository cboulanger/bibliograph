# Bibliograph: Open Source Online Citation & Library Management

[![Run Bibliograph tests](https://github.com/cboulanger/bibliograph/actions/workflows/run-tests.yml/badge.svg)](https://github.com/cboulanger/bibliograph/actions/workflows/run-tests.yml) 
[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=panyasan&url=https://github.com/cboulanger/bibliograph&title=Bibliograph&language=javascript&tags=github&category=software)

Bibliograph is a powerful open source web application for the collaborative
collection, editing and publishing of bibliographic data.

- [Installation](doc/installation.md) 
- [Release Notes](release-notes.md)

**Bibliograph**

- is an application that lets you collect, edit, and publish bibliographic data 
  collaboratively on the web;
  
- has a modern and intuitive user interface that makes the daily life of working 
  with bibliographies and library collections easy and fun;
  
- allows researchers, librarians, teachers and students work together online 
  without having to install software locally;
  
- is fully open source and free to download, install, use and adapt to your 
  particular need.
  
- is based on rock-solid libraries with a large developer communities 
  ([qooxdoo](https://www.qooxdoo.org) for the user interface and 
  [Yii2](http://www.yiiframework.com) for the backend).

**Bibliograph can be used by**

- scholars and librarians who want to publish a library collection or a 
  thematic bibliography online;
  
- groups of researchers who work together in a research project and want to 
  collect and share bibliographic references;
  
- professors and teachers who want to share bibliographic information with their
  students.

## Features

- Organize bibliographic records in static folders or dynamic collections based 
  on queries;
  
- Rich metadata, autocompletion and duplicate detection;

- Allows natural language queries like "title contains hamlet and author 
  begins with shake";

- Fine-grained access control system with users, roles, groups and permissions 
  allows flexible user management and control of who is allowed to view, enter, 
  edit and delete data;

- Unlimited amount of separate databases;

- Imports data from library catalogues (through Z39.50 interface), from 
  various file-based data formats (RIS, BibTeX, Endnote, MODS, and more), and
  from web services.

- LDAP integration to connect to existing LDAP servers;  

- Ability to create and restore snapshot backups of individual databases;

- Easily extendable with custom plugins, for example to connect to other bibliographic 
  databases (experimental support for Zotero is available)

Currently disabled features, can be reimplemented on demand: 

- Formatting of bibliographic records with various citation styles (APA, Chicago, ...)
  using CSL templates and the citeproc style processor (http://citationstyles.org)

- Duplicate detection and display

## Prerequisites, Installation and Deployment
See [here](doc/dev/readme.md).

## Support

- Bugs and feature requests should be registered as [github
issues](https://github.com/cboulanger/bibliograph/issues).

## Development 

- You can [hack the code](doc/dev/readme.md) and make it better;

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

## Credits

Main Open Source libraries/applications

- [qooxdoo JavaScript framework](http://www.qooxdoo.org) 

- [Yii2 Framework](http://www.yiiframework.com)

- [Codeception](https://codeception.com) 

Funding was provided by

- [Department of Law, Humboldt-Universität zu Berlin](http://www.rewi.hu-berlin.de)

Bibliograph is developed using

- [PHPStorm](https://www.jetbrains.com/phpstorm/) (IDE)

- [GitHub](http://github.com) (Version control and code hosting)
f
In particular, the author wishes to thank:

- Gerrit Oldenburg (Humboldt Universität zu Berlin) for supporting and
  maintaining the software at Humboldt Universität.

- Serge Barysiuk for providing assistance with UI generation, and for designing
  the application logo;

- Julika Rosenstock for writing the first version of the end user documentation, 
  Till Rathschlag and Anna Luetkefend for expanding and translating it. 
