<?php
/*
<schema>

  <!--
    Schema for Bookends records
  -->

  <model
    name="bibliograph.plugins.bookends.Model"
    table="no">

    <definition>
      <properties>
        <property name="id" type="int" tag="ID"/>
        <property name="citekey" type="string" tag="CK" />
        <property name="reftype" type="string" tag="RT" update="false"/>
        <property name="author" type="string" tag="AU" />
        <property name="title" type="string" tag="TI" />
        <property name="editor" type="string" tag="ED" />
        <property name="journal" type="string" tag="JO" />
        <property name="volume" type="string" tag="VO" />

        <property name="pages" type="string" tag="PS" />
        <property name="year" type="string" tag="YR" />
        <property name="publisher" type="string" tag="PU" />

        <property name="url" type="string" tag="UR" />
        <property name="booktitle" type="string" tag="BT"/>
        <property name="language" type="string" tag="LN"/>
        <property name="subtitle" type="string" tag="ST"/>
        <property name="abstract" type="string" tag="AB"/>
        <property name="keywords" type="string" tag="KW"/>
        <property name="annote" type="string" tag="AN"/>
        <property name="doi" type="string" tag="DO"/>

        <property name="bibliograph-id" type="string" tag="BI"/>
<!-- is the correct bibtex entry for a publishers place address or location-->
        <property name="address" type="string" tag="AD"/>
        <property name="location" type="string" tag="LO" />

        <!-- fields to update in style and  import filter -->
        <property name="number" type="string" tag="IS"/>
        <property name="note" type="string" tag="NO"/>
        <property name="lccn" type="string" tag="CN"/>
        <property name="series" type="string" tag="SR"/>
        <property name="isbn" type="string" tag="BN"/>
        <property name="issn" type="string" tag="SN"/>
      </properties>

      <aliases>
        <alias for="id">uniqueId</alias>
        <alias for="author">authors</alias>
        <alias for="editor">editors</alias>
        <alias for="citekey">user1</alias>
        <alias for="lccn">user5</alias>
        <alias for="location">user5</alias>
        <alias for="isbn">user6</alias>
        <alias for="issn">user6</alias>
        <alias for="language">user7</alias>
        <alias for="booktitle">volume</alias>
        <alias for="subtitle">title2</alias>
        <alias for="series">title2</alias>
        <alias for="year">thedate</alias>
        <alias for="date">year</alias>
        <alias for="address">location</alias>
        <alias for="doi">user17</alias>
        <alias for="annote">notes</alias>
        <alias for="bibliograph-id">user18</alias>

      </aliases>

    </definition>


    <!-- Bookends data structure -->

    <dataStructure name="dataStructure">

      <referenceTypes name="referenceTypes">

        <referenceType name="article" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="journal" />
            <field name="volume" />
            <field name="number" />
            <field name="date" />
            <field name="month" />
            <field name="pages" />
            <field name="keywords" />
            <field name="url" />
            <field name="doi" />
          </fields>
          <label>Article</label>
        </referenceType>
        <referenceType name="book" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="subtitle" />
            <field name="address" />
            <field name="publisher" />
            <field name="edition" />
            <field name="keywords" />
            <field name="url" />
            <field name="series" />
            <field name="volume" />
            <field name="isbn" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Book (Monograph)</label>
        </referenceType>
        <referenceType name="booklet" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="address" />
            <field name="publisher" />
            <field name="edition" />
            <field name="keywords" />
            <field name="url" />
            <field name="series" />
            <field name="volume" />
            <field name="isbn" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Booklet</label>
        </referenceType>
        <referenceType name="collection" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="subtitle" />
            <field name="address" />
            <field name="publisher" />
            <field name="edition" />
            <field name="keywords" />
            <field name="series" />
            <field name="volume" />
            <field name="url" />
            <field name="isbn" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Book (Edited)</label>
        </referenceType>
        <referenceType name="conference" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="subtitle" />
            <field name="organization" />
            <field name="publisher" />
            <field name="keywords" />
            <field name="address" />
            <field name="publisher" />
            <field name="series" />
            <field name="volume" />
            <field name="url" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Conference Paper</label>
        </referenceType>
        <referenceType name="inbook" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="booktitle" />
            <field name="address" />
            <field name="publisher" />
            <field name="edition" />
            <field name="pages" />
            <field name="keywords" />
            <field name="crossref" />
            <field name="series" />
            <field name="volume" />
            <field name="url" />
            <field name="doi" />
          </fields>
          <label>Book Chapter</label>
        </referenceType>
        <referenceType name="incollection" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="editor" />
            <field name="booktitle" />
            <field name="address" />
            <field name="publisher" />
            <field name="edition" />
            <field name="pages" />
            <field name="keywords" />
            <field name="crossref" />
            <field name="series" />
            <field name="volume" />
            <field name="url" />
            <field name="doi" />
          </fields>
          <label>Chapter in Edited Book</label>
        </referenceType>
        <referenceType name="inproceedings" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="editor" />
            <field name="booktitle" />
            <field name="address" />
            <field name="publisher" />
            <field name="edition" />
            <field name="pages" />
            <field name="keywords" />
            <field name="crossref" />
            <field name="series" />
            <field name="volume" />
            <field name="url" />
            <field name="doi" />
          </fields>
          <label>Paper in Conference Proceedings</label>
        </referenceType>
        <referenceType name="journal" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="subtitle" />
            <field name="journal" />
            <field name="volume" />
            <field name="number" />
            <field name="month" />
            <field name="date" />
            <field name="pages" />
            <field name="keywords" />
            <field name="url" />
            <field name="issn" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Journal Issue</label>
        </referenceType>
        <referenceType name="manual" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="organization" />
            <field name="address" />
            <field name="publisher" />
            <field name="edition" />
            <field name="keywords" />
            <field name="url" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Handbook</label>
        </referenceType>
        <referenceType name="mastersthesis" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="subtitle" />
            <field name="school" />
            <field name="type" />
            <field name="address" />
            <field name="keywords" />
            <field name="url" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Master's Thesis</label>
        </referenceType>
        <referenceType name="misc" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="subtitle" />
            <field name="howpublished" />
            <field name="date" />
            <field name="keywords" />
            <field name="url" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Miscellaneous</label>
        </referenceType>
        <referenceType name="phdthesis" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="subtitle" />
            <field name="school" />
            <field name="type" />
            <field name="address" />
            <field name="keywords" />
            <field name="url" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Ph.D. Thesis</label>
        </referenceType>
        <referenceType name="proceedings" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="subtitle" />
            <field name="publisher" />
            <field name="keywords" />
            <field name="address" />
            <field name="publisher" />
            <field name="series" />
            <field name="volume" />
            <field name="url" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Conference Proceedings</label>
        </referenceType>
        <referenceType name="techreport" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="subtitle" />
            <field name="institution" />
            <field name="type" />
            <field name="number" />
            <field name="address" />
            <field name="publisher" />
            <field name="month" />
            <field name="keywords" />
            <field name="url" />
            <field name="lccn" />
            <field name="doi" />
          </fields>
          <label>Report/Working Paper</label>
        </referenceType>
        <referenceType name="unpublished" bibtex="true">
          <fields>
            <field name="author" />
            <field name="year" />
            <field name="title" />
            <field name="date" />
            <field name="keywords" />
            <field name="url" />
          </fields>
          <label>Unpublished Manuscript</label>
        </referenceType>
      </referenceTypes>
      <fields>
        <field name="reftype" type="string" searchable="no">
          <label>Bibliographic Type</label>
        </field>
        <field name="abstract" bibtex="true" type="string">
          <label>Abstract</label>
        </field>
        <field name="address" bibtex="true" type="string" autocomplete="yes">
          <label>Place</label>
        </field>
        <field name="annote" bibtex="true" type="string">
          <label referencetype="default">Annotation</label>
        </field>
        <field name="author" bibtex="true" type="string" autocomplete="yes" separator=";">
          <label>Author</label>
        </field>
        <field name="booktitle" bibtex="true" type="string" autocomplete="yes">
          <label>Book Title</label>
        </field>
        <field name="doi" bibtex="true" type="string">
          <label>DOI</label>
        </field>
        <field name="edition" bibtex="true" type="string" property="none">
          <label>Edition</label>
        </field>
        <field name="editor" bibtex="true" type="string" autocomplete="yes" separator=";">
          <label>Editors</label>
        </field>
        <field name="isbn" bibtex="true" type="string" property="none">
          <label>ISBN</label>
        </field>
        <field name="issn" bibtex="true" type="string" property="none">
          <label>ISSN</label>
        </field>
        <field name="journal" bibtex="true" type="string" autocomplete="yes">
          <label>Journal</label>
        </field>
        <field name="keywords" bibtex="true" type="string" autocomplete="yes" separator=";">
          <label>Keywords</label>
        </field>
        <field name="language" bibtex="true" type="string" autocomplete="yes">
          <label>Language</label>
        </field>
        <field name="lccn" bibtex="true" type="string">
          <label>Call Number</label>
        </field>
        <field name="location" bibtex="true" type="string">
          <label>Location</label>
        </field>
        <field name="note" bibtex="true" type="string">
          <label>Note</label>
        </field>
        <field name="number" bibtex="true" type="string" property="none">
          <label>Number</label>
        </field>
        <field name="pages" bibtex="true" type="string">
          <label>Pages</label>
        </field>
        <field name="publisher" bibtex="true" type="string" autocomplete="yes">
          <label>Publisher</label>
        </field>
        <field name="series" bibtex="true" type="string" autocomplete="yes">
          <label>Series</label>
        </field>
        <field name="subtitle" bibtex="true" type="string">
          <label>Subtitle</label>
        </field>
        <field name="title" bibtex="true" type="string">
          <label>Title</label>
        </field>
        <field name="url" bibtex="true" type="string">
          <label>Internet Link</label>
        </field>
        <field name="volume" bibtex="true" type="string">
          <label>Volume</label>
        </field>
        <field name="year" bibtex="true" type="string">
          <label>Year</label>
        </field>
        <field name="allFields" fulltext="true" >
          <label>All Fields</label>
        </field>
        <field name="bibliograph-id">
          <label>Bibliograph Key</label>
        </field>
      </fields>
    </dataStructure>

  </model>
</schema>
*/
