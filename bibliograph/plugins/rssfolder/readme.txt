RSS Folder plugin
=================

Status: experimental

This plugin provides importing from RSS feeds. In order to be importable, the
XML must have at least these elements:

<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:enc="http://purl.oclc.org/net/rss_2.0/enc#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" version="2.0">
  <channel>
    <item>
      <link>(URL with bibtex representation of the reference)</link>
      <dc:creator>Viken, Arvid; Granås, Brynhild</dc:creator>
      <dc:title>Tourism destination development: turns and tactics</dc:title>
      <dc:date>2014</dc:date>
    </item>
    ...
  </channel>
</rss>


