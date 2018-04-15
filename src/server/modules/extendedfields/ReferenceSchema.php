<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 03.04.18
 * Time: 23:35
 */

namespace app\modules\extendedfields;

use Yii;
use app\schema\BibtexSchema;

class ReferenceSchema extends BibtexSchema
{
  /**
   * Constructor
   */
  function init()
  {
    parent::init();

    $this->addFields( [
        /*
         * The type of publication
         */
        'type' => [
          'label'     => Yii::t('extendedfields', "Type"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => Yii::t('extendedfields', "Type"),
            'type'      => "combobox",
            'bindStore' => [
              'serviceName'   => "reference",
              'serviceMethod' => "list-field",
              'params'        => ['$datasource', "type"]
            ],
            'autocomplete'  => [
              'enabled'   => true,
              'separator' => null
            ]
          ],
          'index' => "Type"
        ],

        /*
         * Where it is stored
         */
        'location' => [
          'label'     => Yii::t('extendedfields', "Location"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => Yii::t('extendedfields', "Location"),
            'type'      => "combobox",
            'bindStore' => [
              'serviceName'   => "reference",
              'serviceMethod' => "list-field",
              'params'        => ['$datasource', "location"]
            ],
            'autocomplete'  => [
              'enabled'   => true,
              'separator' => null
            ]
          ],
          'index' => "Location"
        ],

        /*
         * A thematic category
         */
        '_category' => [
          'label'     => Yii::t('extendedfields', "Category"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => Yii::t('extendedfields', "Category"),
            'type'      => "combobox",
            'bindStore' => [
              'serviceName'   => "reference",
              'serviceMethod' => "list-field",
              'params'        => ['$datasource', "_category"]
            ],
            'autocomplete'  => [
              'enabled'   => true,
              'separator' => null
            ]
          ],
          'index' => "Category"
        ],

        /*
         * who owns it
         */
        '_owner' => [
          'label'     => Yii::t('extendedfields', "Owner"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => Yii::t('extendedfields', "Owner"),
            'type'      => "combobox",
            'bindStore' => [
              'serviceName'   => "reference",
              'serviceMethod' => "list-field",
              'params'        => ['$datasource', "_owner"]
            ],
            'autocomplete'  => [
              'enabled'   => true,
              'separator' => null
            ]
          ],
          'index' => "Owner"
        ],

        /*
         * When was it ordered
         */
        '_date_ordered' => [
          'label'     => Yii::t('extendedfields', "Date ordered"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => Yii::t('extendedfields', "Date ordered"),
            'type'      => "datefield"
          ],
          'index' => "Date ordered"
        ],

        /*
         * when was it received
         */
        '_date_received' => [
          'label'     => Yii::t('extendedfields', "Date received"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => Yii::t('extendedfields', "Date received"),
            'type'      => "datefield"
          ],
          'index' => "Date received"
        ]
      ]
    );

    $this->addToTypeFields( [
      'type','location','_category','_owner', 'price', '_date_ordered','_date_received'
    ]);
  }
}