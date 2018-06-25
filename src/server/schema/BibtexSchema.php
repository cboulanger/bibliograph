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

namespace app\schema;

use InvalidArgumentException;
use Yii;

/**
 * Class containing data on the BibTex Format
 */
class BibtexSchema extends \app\schema\AbstractReferenceSchema
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
   * @var array
   */
  protected $defaultFieldsBefore = ['reftype', 'citekey'];

  /**
   * An array of fields that are part of the data
   * regardless of the record type and are appended
   * to the record-specific fields
   * @var array
   */
  protected $defaultFieldsAfter = ['keywords', 'abstract', 'note', 'contents'];


  /**
   * The fields that are part of the form by default,
   * regardless of record type
   * @var array
   */
  protected $defaultFormFields = ['reftype', 'citekey'];

  /**
   * The reference types with their fields
   * @var array
   */
  protected $type_fields = [
    'article' => [
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
      'url',
      'doi'
    ],
    'book' => [
      'author',
      'year',
      'title',
      'subtitle',
      'address',
      'publisher',
      'edition',
      'translator',
      'series',
      'volume',
      'isbn',
      'url',
      'doi',
      'lccn'
    ],
    'booklet' => [
      'author',
      'year',
      'title',
      'address',
      'publisher',
      'edition',
      'series',
      'volume',
      'isbn',
      'url',
      'doi',
      'lccn'
    ],
    'collection' => [
      'editor',
      'author',
      'year',
      'title',
      'subtitle',
      'address',
      'publisher',
      'edition',
      'series',
      'volume',
      'isbn',
      'url',
      'doi',
      'lccn'
    ],
    // non-standard use to create conference paper type
    'conference' => [
      'author',
      'year',
      'title',
      'booktitle',
      'url'
    ],
    'inbook' => [
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
      'url',
      'doi'
    ],
    'incollection' => [
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
      'url',
      'doi'
    ],
    'inproceedings' => [
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
      'url',
      'doi'
    ],
    // non-standard journal or journal special issue
    'journal' => [
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
      'issn',
      'url',
      'doi',
      'lccn'
    ],
    'manual' => [
      'author',
      'year',
      'title',
      'organization',
      'address',
      'publisher',
      'edition',
      'url',
      'doi',
      'lccn'
    ],
    'mastersthesis' => [
      'author',
      'year',
      'title',
      'subtitle',
      'school',
      'type',
      'address',
      'url'
    ],
    'misc' => [
      'author',
      'year',
      'title',
      'subtitle',
      'howpublished',
      'date',
      'url'
    ],
    'phdthesis' => [
      'author',
      'year',
      'title',
      'subtitle',
      'school',
      'address',
      'url',
      'lccn'
    ],
    'proceedings' => [
      'editor',
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
      'doi',
      'lccn'
    ],
    'techreport' => [
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
      'doi',
      'lccn'
    ],
    'unpublished' => [
      'author',
      'year',
      'title',
      'date',
      'url'
    ]
  ];


  /**
   * overridden
   * Sets type and field data at runtime since labels need to be translated
   */
  public function init()
  {
    
    parent::init();

    /**
     * The reference type fields
     * @var array
     */
    $this->type_data = [
      'article' => [
        'label' => Yii::t('app', 'Article'),
        'bibtex' => true,
        'csl' => 'article-journal'
      ],
      'book' => [
        'label' => Yii::t('app', 'Book (Monograph)'),
        'bibtex' => true,
        'csl' => 'book'
      ],
      'booklet' => [
        'label' => Yii::t('app', 'Booklet'),
        'bibtex' => true,
        'csl' => 'pamphlet'
      ],
      // non-standard
      'collection' => [
        'label' => Yii::t('app', 'Book (Edited)'),
        'bibtex' => false,
        'csl' => 'book'
      ],
      // non-standard use: normally same as 'proceedings'
      'conference' => [
        'label' => Yii::t('app', 'Conference Paper'),
        'bibtex' => true,
        'csl' => 'paper-conference'
      ],
      'inbook' => [
        'label' => Yii::t('app', 'Book Chapter'),
        'bibtex' => true,
        'csl' => 'chapter'
      ],
      // non-standard
      'incollection' => [
        'label' => Yii::t('app', 'Chapter in Edited Book'),
        'bibtex' => true,
        'csl' => 'chapter'
      ],
      'inproceedings' => [
        'label' => Yii::t('app', 'Paper in Conference Proceedings'),
        'bibtex' => true,
        'csl' => 'chapter'
      ],
      // non-standard
      'journal' => [
        'label' => Yii::t('app', 'Journal Issue'),
        'bibtex' => false,
        'csl' => '???' // => type: periodical?
      ],
      // non-standard use
      'manual' => [
        'label' => Yii::t('app', 'Handbook'),
        'bibtex' => true,
        'csl' => 'book'
      ],
      'mastersthesis' => [
        'label' => Yii::t('app', 'Master\'s Thesis'),
        'bibtex' => true,
        'csl' => 'thesis'
      ],
      'misc' => [
        'label' => Yii::t('app', 'Miscellaneous'),
        'bibtex' => true,
        'csl' => 'manuscript' // ????
      ],
      'phdthesis' => [
        'label' => Yii::t('app', 'Ph.D. Thesis'),
        'bibtex' => true,
        'csl' => 'thesis'
      ],
      'proceedings' => [
        'label' => Yii::t('app', 'Conference Proceedings'),
        'bibtex' => true,
        'csl' => 'book'
      ],
      'techreport' => [
        'label' => Yii::t('app', 'Report/Working Paper'),
        'bibtex' => true,
        'csl' => 'report'
      ],
      'unpublished' => [
        'label' => Yii::t('app', 'Unpublished Manuscript'),
        'bibtex' => true,
        'csl' => 'manuscript'
      ]
    ];

    /**
     * all fields and their metadata
     */
    $this->field_data = [

      /*
       * the reference type
       */
      'reftype' => [
        'label' => Yii::t('app', 'Reference Type'),
        'type' => 'string',
        'csl' => 'type',
        'index' => Yii::t('app', 'Reference Type'),
        'indexEntry' => false,
        'formData' => [
          'type' => 'selectbox',
          'label' => Yii::t('app', 'Reference Type'),
          'bindStore' => [
            'serviceName' => 'reference',
            'serviceMethod' => 'types',
            'params' => ['$datasource']
          ]
        ]
      ],

      /*
       * the citation key
       */
      'citekey' => [
        'label' => Yii::t('app', 'Citation Key'),
        'type' => 'string',
        'csl' => 'id',
        'index' => Yii::t('app', 'Citation Key'),
        'indexEntry' => false,
        'formData' => [
          'type' => 'textfield',
          'label' => Yii::t('app', 'Citation key')
        ]
      ],
      'abstract' => [
        'label' => Yii::t('app', 'Abstract'),
        'type' => 'string',
        'indexEntry' => false,
        'bibtex' => true,
        'formData' => [
          'type' => 'textarea',
          'lines' => 3
        ],
        'csl' => 'abstract',
        'index' => Yii::t('app', 'Abstract')
      ],
      // this is used for publisher-place or for author address
      'address' => [
        'label' => Yii::t('app', 'Place'),
        'type' => 'string',
        'bibtex' => true,
        'indexEntry' => true,
        'formData' => [
          'type' => 'textfield',
          'autocomplete' => [
            'enabled' => true,
            'separator' => null
          ]
        ],
        'csl' => 'publisher-place',
        'index' => Yii::t('app', 'Place')
      ],
      'affiliation' => [
        'label' => Yii::t('app', 'Affiliation'),
        'autocomplete' => ['separator' => null],
        'indexEntry' => true,
        'type' => 'string',
        'bibtex' => true,
        'csl' => false // ???
      ],
      'annote' => [
        'label' => Yii::t('app', 'Annotation'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => 'annote'
      ],
      'author' => [
        'label' => [
          'default' => Yii::t('app', 'Authors'),
          'conference' => Yii::t('app', 'Authors'),
          'collection' => Yii::t('app', 'Authors'),
          'proceedings' => Yii::t('app', 'Authors')
        ],
        'type' => 'string',
        'indexEntry' => true,
        'bibtex' => true,
        'separator' => ';',
        'formData' => [
          'type' => 'textarea',
          'lines' => 3,
          'autocomplete' => [
            'enabled' => true,
            'separator' => "\n"
          ]
        ],
        'csl' => 'author',
        'index' => Yii::t('app', 'Author')
      ],
      'booktitle' => [
        'label' => Yii::t('app', 'Book Title'),
        'autocomplete' => ['separator' => null],
        'type' => 'string',
        'indexEntry' => true,
        'bibtex' => true,
        'formData' => [
          'type' => 'textfield',
          'fullWidth' => true,
          'autocomplete' => [
            'enabled' => true,
            'separator' => null
          ]
        ],
        'csl' => 'container-title',
        'index' => Yii::t('app', 'Book Title')
      ],
      'contents' => [
        'label' => Yii::t('app', 'Contents'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => false
      ],
      'copyright' => [
        'label' => Yii::t('app', 'Copyright'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => null
      ],
      'crossref' => [
        'label' => Yii::t('app', 'Cross Reference'),
        'type' => 'string',
        'bibtex' => true,
        'formData' => [
          'type' => 'textfield',
          'autocomplete' => [
            'enabled' => true,
            'source' => 'citekey'
          ]
        ],
        'csl' => 'references'
      ],
      'date' => [
        'label' => Yii::t('app', 'Date'),
        'type' => 'date',
        'bibtex' => true,
        'csl' => false,
        'index' => Yii::t('app', 'Date')
      ],
      'doi' => [
        'label' => Yii::t('app', 'DOI'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => 'doi',
        'index' => 'DOI'
      ],
      'edition' => [
        'label' => Yii::t('app', 'Edition'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => 'edition'
      ],
      'editor' => [
        'label' => Yii::t('app', 'Editors'),
        'type' => 'string',
        'indexEntry' => true,
        'bibtex' => true,
        'separator' => ';',
        'formData' => [
          'type' => 'textarea',
          'lines' => 3,
          'autocomplete' => [
            'enabled' => true,
            'separator' => "\n"
          ]
        ],
        'csl' => 'author',
        'index' =>  Yii::t('app', 'Editor')
      ],
      'howpublished' => [
        'label' => Yii::t('app', 'Published As'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => false
      ],
      'institution' => [
        'label' => Yii::t('app', 'Institution'),
        'type' => 'string',
        'indexEntry' => true,
        'bibtex' => true,
        'formData' => [
          'type' => 'textfield',
          'autocomplete' => [
            'enabled' => true,
            'separator' => null
          ]
        ],
        'csl' => false
      ],
      'isbn' => [
        'label' => Yii::t('app', 'ISBN'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => 'ISBN',
        'index' => Yii::t('app', 'ISBN')
      ],
      'issn' => [
        'label' => Yii::t('app', 'ISSN'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => 'ISSN'
      ],
      'journal' => [
        'label' => Yii::t('app', 'Journal'),
        'type' => 'string',
        'indexEntry' => true,
        'bibtex' => true,
        'formData' => [
          'type' => 'textfield',
          'fullWidth' => true,
          'autocomplete' => [
            'enabled' => true,
            'separator' => null
          ]
        ],
        'csl' => 'container-title',
        'index' => Yii::t('app', 'Journal')
      ],
      // don't know what this is for, anyways
      'key' => [
        'label' => Yii::t('app', 'Key'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => false
      ],
      'keywords' => [
        'label' => Yii::t('app', 'Keywords'),
        'type' => 'string',
        'indexEntry' => true,
        'bibtex' => true,
        'separator' => ';',
        'formData' => [
          'type' => 'textarea',
          'lines' => 3,
          'autocomplete' => [
            'enabled' => true,
            'separator' => "\n"
          ]
        ],
        'csl' => 'keyword',
        'index' => Yii::t('app', 'Keywords')
      ],
      'language' => [
        'label' => Yii::t('app', 'Language'),
        'autocomplete' => ['separator' => null],
        'type' => 'string',
        'indexEntry' => true,
        'bibtex' => true,
        'csl' => false // ???
      ],
      'lccn' => [
        'label' => Yii::t('app', 'Call Number'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => 'call-number',
        'index' => 'Call Number'
      ],
      // field to store where the book is kept
      'location' => [
        'label' => Yii::t('app', 'Location'),
        'type' => 'string',
        'indexEntry' => true,
        'autocomplete' => ['separator' => null],
        'bibtex' => true,
        'csl' => false,
        'index' => Yii::t('app', 'Location')
      ],
      'month' => [
        'label' => Yii::t('app', 'Month'),
        'type' => 'string',
        'indexEntry' => true,
        'autocomplete' => ['separator' => null],
        'bibtex' => true,
        'csl' => false
      ],
      'note' => [
        'label' => Yii::t('app', 'Note'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => 'note',
        'index' => Yii::t('app', 'Note')
      ],
      'number' => [
        'label' => Yii::t('app', 'Number'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => 'number'
      ],
      'organization' => [
        'label' => Yii::t('app', 'Organization'),
        'type' => 'string',
        'indexEntry' => true,
        'formData' => [
          'type' => 'textfield',
          'autocomplete' => [
            'enabled' => true,
            'separator' => null
          ]
        ],
        'csl' => false
      ],
      'pages' => [
        'label' => Yii::t('app', 'Pages'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => 'page'
      ],
      'price' => [
        'label' => Yii::t('app', 'Price'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => "price",
        'public' => false
      ],
      'publisher' => [
        'label' => Yii::t('app', 'Publisher'),
        'type' => 'string',
        'indexEntry' => true,
        'bibtex' => true,
        'formData' => [
          'type' => 'textfield',
          'autocomplete' => [
            'enabled' => true,
            'separator' => null
          ]
        ],
        'csl' => 'publisher',
        'index' => Yii::t('app', 'Publisher')
      ],
      'school' => [
        'label' => Yii::t('app', 'University'),
        'type' => 'string',
        'indexEntry' => true,
        'bibtex' => true,
        'formData' => [
          'type' => 'textfield',
          'autocomplete' => [
            'enabled' => true,
            'separator' => null
          ]
        ],
        'csl' => false
      ],
      'series' => [
        'label' => Yii::t('app', 'Series'),
        'type' => 'string',
        'bibtex' => true,
        'indexEntry' => true,
        'formData' => [
          'type' => 'textfield',
          'autocomplete' => [
            'enabled' => true,
            'separator' => null
          ]
        ],
        'csl' => 'collection-title',
        'index' => Yii::t('app', 'Series'),
      ],
      'size' => [
        'label' => Yii::t('app', 'Size'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => false
      ],
      'subtitle' => [
        'label' => Yii::t('app', 'Subtitle'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => false
      ],
      'title' => [
        'label' => Yii::t('app', 'Title'),
        'type' => 'string',
        'bibtex' => true,
        'formData' => [
          'type' => 'textfield',
          'fullWidth' => true
        ],
        'csl' => 'title',
        'index' => Yii::t('app', 'Title')
      ],
      'translator' => [
        'label' => Yii::t('app', 'Translator'),
        'type' => 'string',
        'bibtex' => true,
        'indexEntry' => true,
        'separator' => ";",
        'formData' => [
          'type' => 'textfield',
          'fullWidth' => true,
          'autocomplete' => [
            'enabled' => true,
            'separator' => ";"
          ]
        ],
        'csl' => 'translator'
      ],
      'type' => [
        'label' => Yii::t('app', 'Type'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => false
      ],
      'url' => [
        'label' => Yii::t('app', 'Internet Link'),
        'type' => 'link',
        'bibtex' => true,
        'formData' => [
          'type' => 'textfield',
          'fullWidth' => true
        ],
        'csl' => 'URL'
      ],
      'volume' => [
        'label' => Yii::t('app', 'Volume'),
        'type' => 'string',
        'bibtex' => true,
        'csl' => 'volume'
      ],
      'year' => [
        'label' => Yii::t('app', 'Year'),
        'type' => 'int',
        'bibtex' => true,
        'csl' => 'issued',
        'index' => Yii::t('app', 'Year')
      ]
    ];
  }

  /**
   * Converts a data record into the citeproc schema.
   * @todo should be in exporter class
   * @param $input
   * @return object
   */
  public function toCslRecord(array $input)
  {
    $record = array();
    foreach ($input as $key => $value) {
      // skip empy values
      if ($value === null or $value === "") continue;

      // get field data, if exists for the field
      try {
        $fieldData = $this->getFieldData($key);
      } catch (InvalidArgumentException $e) {
        continue;
      }

      // transform field
      switch ($key) {
        case "reftype":
          $typeData = $this->getTypeData($value);
          $record['type'] = $typeData['csl'];
          break;

        case "author":
          $authors = explode("\n", $value);
          $authors_out = array();
          foreach ($authors as $author) {
            $author_out = array();
            if (strstr($author, ",")) {
              $parts = explode(",", $author);
              $author_out['family'] = trim($parts[0]);
              $author_out['given'] = trim($parts[1]);
              $author_out['parse-names'] = "true";
            } else {
              $author_out['family'] = $author;
              $author_out['parse-names'] = "false";
            }
            $authors_out[] = (object)$author_out;
          }
          $record['author'] = $authors_out;
          break;

        case "date":
          $record['issued'] = (object)array(
            'raw' => $value
          );
          break;

        case "keywords":
          $record['keyword'] = explode("\n", $value);
          break;

        case "year":
          $record['issued'] = (object)array(
            'date-parts' => array(array($value))
          );
          break;

        default:
          $csl = $fieldData['csl'];
          if ($csl) {
            $record[$csl] = $value;
          }
          break;
      }
    }
    return (object)$record;
  }
}
