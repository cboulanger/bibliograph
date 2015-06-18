Bibutis plugin
==============

Status: production

This plugin provides formatting of bibliographic data into various export formats
and translating the formats into each other. Currently, the following formats
are supported:

- Endnote export format
- Endnote XML
- ISI
- MODS
- PubMed
- RIS

It makes use of the bibutis package by Chris Putnam:

http://www.scripps.edu/~cdputnam/software/bibutils/#bib2xml

You will have to install the bibutis binaries, either compiling
them yourself, using one of the prebuilt binaries from the website,
or a package of your linux distribution. Make sure the executables
are on the PATH, or alternative define the BIBUTILS_PATH constant
in your config.php file.