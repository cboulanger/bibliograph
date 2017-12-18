<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "database1_data_Reference".
 *
 * @property integer $id
 * @property string $created
 * @property string $modified
 * @property string $citekey
 * @property string $reftype
 * @property string $abstract
 * @property string $address
 * @property string $affiliation
 * @property string $annote
 * @property string $author
 * @property string $booktitle
 * @property string $subtitle
 * @property string $contents
 * @property string $copyright
 * @property string $crossref
 * @property string $date
 * @property string $doi
 * @property string $edition
 * @property string $editor
 * @property string $howpublished
 * @property string $institution
 * @property string $isbn
 * @property string $issn
 * @property string $journal
 * @property string $key
 * @property string $keywords
 * @property string $language
 * @property string $lccn
 * @property string $location
 * @property string $month
 * @property string $note
 * @property string $number
 * @property string $organization
 * @property string $pages
 * @property string $price
 * @property string $publisher
 * @property string $school
 * @property string $series
 * @property string $size
 * @property string $title
 * @property string $translator
 * @property string $type
 * @property string $url
 * @property string $volume
 * @property string $year
 * @property string $createdBy
 * @property string $modifiedBy
 * @property string $hash
 * @property integer $markedDeleted
 * @property integer $attachments
 */
class Reference extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'database1_data_Reference';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified'], 'safe'],
            [['abstract', 'annote', 'contents', 'note'], 'string'],
            [['markedDeleted', 'attachments'], 'integer'],
            [['citekey', 'affiliation', 'crossref', 'date', 'doi', 'edition', 'month', 'size', 'type', 'volume', 'createdBy', 'modifiedBy'], 'string', 'max' => 50],
            [['reftype', 'issn', 'key', 'language', 'year'], 'string', 'max' => 20],
            [['address', 'author', 'booktitle', 'subtitle', 'editor', 'howpublished', 'institution', 'keywords', 'lccn', 'title', 'url'], 'string', 'max' => 255],
            [['copyright', 'journal', 'location', 'organization', 'publisher', 'school'], 'string', 'max' => 150],
            [['isbn', 'number', 'pages', 'price'], 'string', 'max' => 30],
            [['series'], 'string', 'max' => 200],
            [['translator'], 'string', 'max' => 100],
            [['hash'], 'string', 'max' => 40],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'created' => Yii::t('app', 'Created'),
            'modified' => Yii::t('app', 'Modified'),
            'citekey' => Yii::t('app', 'Citekey'),
            'reftype' => Yii::t('app', 'Reftype'),
            'abstract' => Yii::t('app', 'Abstract'),
            'address' => Yii::t('app', 'Address'),
            'affiliation' => Yii::t('app', 'Affiliation'),
            'annote' => Yii::t('app', 'Annote'),
            'author' => Yii::t('app', 'Author'),
            'booktitle' => Yii::t('app', 'Booktitle'),
            'subtitle' => Yii::t('app', 'Subtitle'),
            'contents' => Yii::t('app', 'Contents'),
            'copyright' => Yii::t('app', 'Copyright'),
            'crossref' => Yii::t('app', 'Crossref'),
            'date' => Yii::t('app', 'Date'),
            'doi' => Yii::t('app', 'Doi'),
            'edition' => Yii::t('app', 'Edition'),
            'editor' => Yii::t('app', 'Editor'),
            'howpublished' => Yii::t('app', 'Howpublished'),
            'institution' => Yii::t('app', 'Institution'),
            'isbn' => Yii::t('app', 'Isbn'),
            'issn' => Yii::t('app', 'Issn'),
            'journal' => Yii::t('app', 'Journal'),
            'key' => Yii::t('app', 'Key'),
            'keywords' => Yii::t('app', 'Keywords'),
            'language' => Yii::t('app', 'Language'),
            'lccn' => Yii::t('app', 'Lccn'),
            'location' => Yii::t('app', 'Location'),
            'month' => Yii::t('app', 'Month'),
            'note' => Yii::t('app', 'Note'),
            'number' => Yii::t('app', 'Number'),
            'organization' => Yii::t('app', 'Organization'),
            'pages' => Yii::t('app', 'Pages'),
            'price' => Yii::t('app', 'Price'),
            'publisher' => Yii::t('app', 'Publisher'),
            'school' => Yii::t('app', 'School'),
            'series' => Yii::t('app', 'Series'),
            'size' => Yii::t('app', 'Size'),
            'title' => Yii::t('app', 'Title'),
            'translator' => Yii::t('app', 'Translator'),
            'type' => Yii::t('app', 'Type'),
            'url' => Yii::t('app', 'Url'),
            'volume' => Yii::t('app', 'Volume'),
            'year' => Yii::t('app', 'Year'),
            'createdBy' => Yii::t('app', 'Created By'),
            'modifiedBy' => Yii::t('app', 'Modified By'),
            'hash' => Yii::t('app', 'Hash'),
            'markedDeleted' => Yii::t('app', 'Marked Deleted'),
            'attachments' => Yii::t('app', 'Attachments'),
        ];
    }
}
