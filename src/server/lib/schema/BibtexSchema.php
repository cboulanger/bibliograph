<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

namespace lib\schema;

use Yii;

/**
 * Class containing data on the BibTex Format
 */
class BibtexSchema extends \lib\schema\AbstractSchema
{
  /**
   * The default reference type
   * @var string
   */
  protected $defaultType = 'article';

  /**
   * An array of fields that are part of the data
   * regardless of the record type and are prepended
   * to the record-specific fields
   * @var unknown_type
   */
  protected $defaultFieldsBefore = array('reftype','citekey');

  /**
   * An array of fields that are part of the data
   * regardless of the record type and are appended
   * to the record-specific fields
   * @var unknown_type
   */
  protected $defaultFieldsAfter = array('keywords','abstract','note','contents');


  /**
   * The fields that are part of the form by default,
   * regardless of record type
   * @var array
   */
  protected $defaultFormFields = array( 'reftype','citekey' );

  /**
   * The reference types with their fields
   * @var array
   */
  protected $type_fields =  array (
    'article' => array (
      'author',
      'year',
      'title',
      'journal',
      'volume',
      'number',
      'date',
      'month',
      'pages',
      'translator',
      'url'
    ),
    'book' => array (
      'author',
      'year',
      'title',
      'subtitle',
      'address',
      'publisher',
      'edition',
      'translator',
      'url',
      'series',
      'volume',
      'isbn',
      'lccn'
    ),
    'booklet' => array (
      'author',
      'year',
      'title',
      'address',
      'publisher',
      'edition',
      'url',
      'series',
      'volume',
      'isbn',
      'lccn'
    ),
    'collection' => array (
      'author',
      'year',
      'title',
      'subtitle',
      'address',
      'publisher',
      'edition',
      'series',
      'volume',
      'url',
      'isbn',
      'lccn'
     ),
    // non-standard use to create conference paper type
    'conference' => array (
      'author',
      'year',
      'title',
      'booktitle',
      'url'
     ),
     'inbook' => array (
      'author',
      'year',
      'title',
      'booktitle',
      'address',
      'publisher',
      'edition',
      'pages',
      'crossref',
      'series',
      'volume',
      'translator',
      'url'
    ),
    'incollection'  => array (
      'author',
      'year',
      'title',
      'editor',
      'booktitle',
      'address',
      'publisher',
      'edition',
      'pages',
      'crossref',
      'series',
      'volume',
      'url'
    ),
    'inproceedings' => array (
      'author',
      'year',
      'title',
      'editor',
      'booktitle',
      'address',
      'publisher',
      'edition',
      'pages',
      'crossref',
      'series',
      'volume',
      'url'
    ),
    'journal' => array (
      'author',
      'year',
      'title',
      'subtitle',
      'journal',
      'volume',
      'number',
      'month',
      'date',
      'pages',
      'url',
      'issn',
      'lccn'
    ),
    'manual' => array (
      'author',
      'year',
      'title',
      'organization',
      'address',
      'publisher',
      'edition',
      'url',
      'lccn'
    ),
    'mastersthesis' => array (
      'author',
      'year',
      'title',
      'subtitle',
      'school',
      'type',
      'address',
      'url',
      'lccn'
    ),
    'misc' => array (
      'author',
      'year',
      'title',
      'subtitle',
      'howpublished',
      'date',
      'url',
      'lccn'
    ),
    'phdthesis' => array (
      'author',
      'year',
      'title',
      'subtitle',
      'school',
      'address',
      'url',
      'lccn'
    ),
    'proceedings' => array (
      'author',
      'year',
      'title',
      'subtitle',
      'organization',
      'publisher',
      'address',
      'publisher',
      'series',
      'volume',
      'url',
      'lccn'
    ),
    'techreport' => array (
      'author',
      'year',
      'title',
      'subtitle',
      'institution',
      'number',
      'address',
      'publisher',
      'month',
      'url',
      'lccn'
    ),
    'unpublished' => array (
      'author',
      'year',
      'title',
      'date',
      'url'
     )
    );


  /**
   * Constructor. Overwrites the public variables so that the translation
   * features can be used
   * @return \bibliograph_schema_BibtexSchema
   */
  public function __construct()
  {

    /**
     * The reference type fields
     * @var array
     */
    $this->type_data = array (
        'article' => array (
          'label'         => _('Article'),
          'bibtex'        => true,
          'csl'           => 'article-journal'
        ),
        'book' => array (
          'label'         => _('Book (Monograph)'),
          'bibtex'        => true,
          'csl'           => 'book'
        ),
        'booklet' => array (
          'label'         => _('Booklet'),
          'bibtex'        => true,
          'csl'           => 'pamphlet'
        ),
        // non-standard
        'collection' => array (
          'label'         => _('Book (Edited)'),
          'bibtex'        => false,
          'csl'           => 'book'
        ),
        // non-standard use: normally same as 'proceedings'
        'conference' => array (
          'label'         => _('Conference Paper'),
          'bibtex'        => true,
          'csl'           => 'paper-conference'
         ),
        'inbook' => array (
          'label'         => _('Book Chapter'),
          'bibtex'        => true,
          'csl'           => 'chapter'
        ),
        // non-standard
        'incollection'  => array (
          'label'         => _('Chapter in Edited Book'),
          'bibtex'        => true,
          'csl'           => 'chapter'
        ),
        'inproceedings' => array (
          'label'         => _('Paper in Conference Proceedings'),
          'bibtex'        => true,
          'csl'           => 'chapter'
        ),
        // non-standard
        'journal' => array (
          'label'         => _('Journal Issue'),
          'bibtex'        => false,
          'csl'           => '???' // => type: periodical?
          ),
        // non-standard use
        'manual' => array (
          'label'         => _('Handbook'),
          'bibtex'        => true,
          'csl'           => 'book'
        ),
        'mastersthesis' => array (
          'label'         => _('Master\'s Thesis'),
          'bibtex'        => true,
          'csl'           => 'thesis'
          ),
        'misc' => array (
          'label'         => _('Miscellaneous'),
          'bibtex'        => true,
          'csl'           => 'manuscript' // ????
        ),
        'phdthesis' => array (
          'label'         => _('Ph.D. Thesis'),
          'bibtex'        => true,
          'csl'           => 'thesis'
        ),
        'proceedings' => array (
          'label'         => _('Conference Proceedings'),
          'bibtex'        => true,
          'csl'           => 'book'
        ),
        'techreport' => array (
          'label'         => _('Report/Working Paper'),
          'bibtex'        => true,
          'csl'           => 'report'
        ),
        'unpublished' => array (
          'label'         => _('Unpublished Manuscript'),
          'bibtex'        => true,
          'csl'           => 'manuscript'
        )
      );

      /**
       * all fields and their metadata
       */
      $this->field_data = array (

        /*
         * the reference type
         */
        'reftype' => array(
          'label'     => _('Bibliographic Type'),
          'type'      => 'string',
          'csl'       => 'type',
          'index'     => 'reftype',
          'indexEntry' => false,
          'formData'  => array(
            'type'  => 'selectbox',
            'label' => Yii::t('app', 'Reference Type' ),
            'bindStore' => array(
              'serviceName'   => 'reference',
              'serviceMethod' => 'types',
              'params'        => array( '$datasource' )
            )
          )
        ),

        /*
         * the citation key
         */
        'citekey' => array(
          'label'     => _('Citation Key'),
          'type'      => 'string',
          'csl'       => 'id',
          'index'     => 'Citation key',
          'indexEntry' => false,
          'formData'  => array(
            'type'    => 'textfield',
            'label'   => _('Citation key')
          )
        ),
        'abstract' => array(
          'label'     => _('Abstract'),
          'type'      => 'string',
          'indexEntry' => false,
          'bibtex'    => true,
          'formData'  => array(
            'type'      => 'textarea',
            'lines'     => 3
          ),
          'csl'       => 'abstract',
          'index'     => 'Abstract'
        ),
        // this is used for publisher-place or for author address
        'address' => array(
          'label'     => _('Place'),
          'type'      => 'string',
          'bibtex'    => true,
          'indexEntry' => true,
          'formData'  => array(
            'type'      => 'textfield',
            'autocomplete'  => array(
              'enabled'   => true,
              'separator' => null
            )
          ),
          'csl'       => 'publisher-place',
          'index'     => 'Place'
        ),
        'affiliation' => array(
          'label'     => _('Affiliation'),
          'autocomplete'  => array('separator' => null ),
          'indexEntry' => true,
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => false // ???
        ),
        'annote' => array(
          'label'     => _('Annotation'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => 'annote'
        ),
        'author' => array(
          'label'     => array (
                          'default'      => _('Authors'),
                          'conference'   => _('Authors'),
                          'collection'   => _('Editors'),
                          'proceedings'  => _('Editors')
                         ),
          'type'      => 'string',
          'indexEntry' => true,
          'bibtex'    => true,
          'separator' => ';',
          'formData'  => array(
            'type'          => 'textarea',
            'lines'         => 3,
            'autocomplete'  => array(
              'enabled'   => true,
              'separator' => "\n"
            )
          ),
          'csl'       => 'author',
          'index'     => 'Author'
        ),
        'booktitle' => array(
          'label'     => array (
                          'default'      => _('Book Title'),
                          'conference'   => _('Conference Title')
                         ),
          'autocomplete'  => array('separator' => null ),
          'type'      => 'string',
          'indexEntry' => true,
          'bibtex'    => true,
          'formData'  => array(
            'type'          => 'textfield',
            'fullWidth'     => true,
            'autocomplete'  => array(
              'enabled'       => true,
              'separator'     => null
            )
          ),
          'csl'       => 'container-title',
          'index'     => 'Book Title'
        ),
        'contents' => array(
          'label'     => _('Contents'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => false
        ),
        'copyright' => array(
          'label'     => _('Copyright'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => null
        ),
        'crossref' => array(
          'label'     => _('Cross Reference'),
          'type'      => 'string',
          'bibtex'    => true,
          'formData'  => array(
            'type'      => 'textfield',
            'autocomplete'  => array(
              'enabled'   => true,
              'source'    => 'citekey'
            )
          ),
          'csl'       => 'references'
        ),
        'date'   => array(
          'label'     => _('Date'),
          'type'      => 'date',
          'bibtex'    => true,
          'csl'       => false,
          'index'     => 'Date'
        ),
        'edition' => array(
          'label'     => _('Edition'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => 'edition'
        ),
        'editor' => array(
          'label'     => _('Editors'),
          'type'      => 'string',
          'indexEntry' => true,
          'bibtex'    => true,
          'separator' => ';',
          'formData'  => array(
            'type'          => 'textarea',
            'lines'         => 3,
            'autocomplete'  => array(
              'enabled'   => true,
              'separator' => "\n"
            )
          ),
          'csl'       => 'author',
          'index'     => 'Editor'
        ),
        'howpublished' => array(
          'label'     => _('Published As'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => false
        ),
        'institution' => array(
          'label'     => _('Institution'),
          'type'      => 'string',
          'indexEntry' => true,
          'bibtex'    => true,
          'formData'  => array(
            'type'          => 'textfield',
            'autocomplete'  => array(
              'enabled'       => true,
              'separator'     => null
            )
          ),
          'csl'       => false
        ),
        'isbn'   => array(
          'label'     => _('ISBN'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => 'ISBN'
        ),
        'issn'   => array(
          'label'     => _('ISSN'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => 'ISSN'
        ),
        'journal' => array(
          'label'     => _('Journal'),
          'type'      => 'string',
          'indexEntry' => true,
          'bibtex'    => true,
          'formData'  => array(
            'type'          => 'textfield',
            'fullWidth'     => true,
            'autocomplete'  => array(
              'enabled'       => true,
              'separator'     => null
            )
          ),
          'csl'       => 'container-title',
          'index'     => 'Journal'
        ),
        // don't know what this is for, anyways
        'key' => array(
          'label'     => _('Key'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => false
        ),
        'keywords' => array(
          'label'     => _('Keywords'),
          'type'      => 'string',
          'indexEntry' => true,
          'bibtex'    => true,
          'separator' => ';',
          'formData'  => array(
            'type'          => 'textarea',
            'lines'         => 3,
            'autocomplete'  => array(
              'enabled'       => true,
              'separator'     => "\n"
            )
          ),
          'csl'       => 'keyword',
          'index'     => 'Keywords'
        ),
        'language' => array(
          'label'     => _('Language'),
          'autocomplete'  => array('separator' => null ),
          'type'      => 'string',
          'indexEntry' => true,
          'bibtex'    => true,
          'csl'       => false // ???
        ),
        'lccn' => array(
          'label'     => _('Call Number'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => 'call-number',
          'index'     => 'Call Number'
        ),
        // field to store where the book is kept
        'location' => array(
          'label'     => _('Location'),
          'type'      => 'string',
          'indexEntry' => true,
          'autocomplete'  => array('separator' => null ),
          'bibtex'    => true,
          'csl'       => false,
          'index'     => 'Location'
        ),
        'month' => array(
          'label'     => _('Month'),
          'type'      => 'string',
          'indexEntry' => true,
          'autocomplete'  => array('separator' => null ),
          'bibtex'    => true,
          'csl'       => false,
          'index'     => 'Month'
        ),
        'note' => array(
          'label'     => _('Note'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => 'note'
        ),
        'number' => array(
          'label'     => _('Number'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => 'number'
        ),
        'organization' => array(
          'label'     => _('Organization'),
          'type'      => 'string',
          'indexEntry' => true,
          'formData'  => array(
            'type'          => 'textfield',
            'autocomplete'  => array(
              'enabled'       => true,
              'separator'     => null
            )
          ),
          'csl'       => false
        ),
        'pages' => array(
          'label'     => _('Pages'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => 'page'
        ),
        'price' => array(
          'label'     => _('Price'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => "price",
          'public'    => false
        ),
        'publisher' => array(
          'label'         => _('Publisher'),
          'type'          => 'string',
          'indexEntry' => true,
          'bibtex'        => true,
          'formData'  => array(
            'type'          => 'textfield',
            'autocomplete'  => array(
              'enabled'       => true,
              'separator'     => null
            )
          ),
          'csl'       => 'publisher',
          'index'     => 'Publisher'
        ),
        'school' => array(
          'label'         => _('University'),
          'type'          => 'string',
          'indexEntry' => true,          
          'bibtex'        => true,
          'formData'  => array(
            'type'          => 'textfield',
            'autocomplete'  => array(
              'enabled'       => true,
              'separator'     => null
            )
          ),
          'csl'       => false
        ),
        'series' => array(
          'label'     => _('Series'),
          'type'      => 'string',
          'bibtex'    => true,
          'indexEntry' => true,
          'formData'  => array(
            'type'          => 'textfield',
            'autocomplete'  => array(
              'enabled'       => true,
              'separator'     => null
            )
          ),
          'csl'       => 'collection-title'
        ),
        'size'   => array(
          'label'     => _('Size'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => false
        ),
        'subtitle' => array(
          'label'     => _('Subtitle'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => false
        ),
        'title' => array(
          'label'     => _('Title'),
          'type'      => 'string',
          'bibtex'    => true,
          'formData'  => array(
            'type'          => 'textfield',
            'fullWidth'     => true
          ),
          'csl'       => 'title',
          'index'     => 'Title'
        ),
        'translator' => array(
          'label'     => _('Translator'),
          'type'      => 'string',
          'bibtex'    => true,
          'indexEntry' => true,
          'separator' => ";",
          'formData'  => array(
            'type'          => 'textfield',
            'fullWidth'     => true,
            'autocomplete'  => array(
              'enabled'       => true,
              'separator'     => ";"
            )
          ),
          'csl'       => 'translator'
        ),
        'type' => array(
          'label'     => _('Type'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => false
        ),
        'url' => array(
          'label'     => _('Internet Link'),
          'type'      => 'link',
          'bibtex'    => true,
          'formData'  => array(
            'type'          => 'textfield',
            'fullWidth'     => true
          ),
          'csl'       => 'URL'
        ),
        'volume' => array(
          'label'     => _('Volume'),
          'type'      => 'string',
          'bibtex'    => true,
          'csl'       => 'volume'
        ),
        'year'     => array(
          'label'     => _('Year'),
          'type'      => 'int',
          'bibtex'    => true,
          'csl'       => 'issued',
          'index'     => array('Year','Date')
        )
      );

    parent::__construct();
  }

  /**
   * Converts a data record into the citeproc schema.
   * @todo should be in exporter class
   * @param $input
   * @return object
   */
  public function toCslRecord( array $input )
  {
    $record = array();
    foreach( $input as $key => $value )
    {
      // skip empy values
      if ( $value === null or $value === "" ) continue;

      // get field data, if exists for the field
      try {
        $fieldData = $this->getFieldData( $key );
      } catch( InvalidArgumentException $e ) {
        continue;
      }

      // transform field
      switch( $key )
      {
        case "reftype":
          $typeData = $this->getTypeData( $value );
          $record['type'] = $typeData['csl'];
          break;

        case "author":
          $authors = explode( "\n", $value );
          $authors_out = array();
          foreach( $authors as $author )
          {
            $author_out = array();
            if( strstr( $author, "," ) )
            {
              $parts = explode(",",$author);
              $author_out['family'] = trim( $parts[0] );
              $author_out['given']  = trim( $parts[1] );
              $author_out['parse-names'] = "true";
            }

            else
            {
              $author_out['family'] = $author;
              $author_out['parse-names'] = "false";
            }
            $authors_out[] = (object) $author_out;
          }
          $record['author'] = $authors_out;
          break;

        case "date":
          $record['issued'] = (object) array(
            'raw' => $value
          );
          break;

        case "keywords":
          $record['keyword'] = explode( "\n", $value );
          break;

        case "year":
          $record['issued'] = (object) array(
            'date-parts' => array( array( $value ) )
          );
          break;

        default:
          $csl = $fieldData['csl'];
          if( $csl ) {
            $record[ $csl ] = $value;
          }
          break;
      }
    }
    return (object) $record;
  }
}
