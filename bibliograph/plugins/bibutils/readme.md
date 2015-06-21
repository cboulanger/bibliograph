Bibutils plugin
===============

Status: production

This plugin extends the formatting of references beyon BibTeX, which is built in,
and the import of data in a variety of formats, including RIS, Endnote, PubMed,
ISI and MODS.

The plugin requires the [Bibutils](http://bibutils.refbase.org/) binaries to be installed
on the server. If they are not on the default `PATH` available to the webserver
process, you need to set the `BIBUTILS_PATH` constant in the `server.conf.php` 
configuration file.