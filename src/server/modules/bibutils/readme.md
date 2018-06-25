# Bibutils plugin

Status: beta

This plugin provides formatting of bibliographic data into various export formats
and translating the formats into each other. Currently, the following formats
are supported:

- Endnote export format
- Endnote XML
- ISI
- MODS
- PubMed
- RIS

You will have to install the [Bibutils](http://bibutils.refbase.org/) binaries, 
either compiling them yourself, using one of the prebuilt binaries from the website,
or a package of your linux distribution. Make sure the executables
are on the PATH, or alternative define the `BIBUTILS_PATH` constant
in the `server/config/parts/constants.php` file.
