<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 03.04.18
 * Time: 23:35
 */

namespace modules\extendedfields;

use app\schema\BibtexSchema;

class Schema extends BibtexSchema
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
          'label'     => _("Type"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => _("Type"),
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
          ]
        ],

        /*
         * Where it is stored
         */
        'location' => [
          'label'     => _("location"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => _("location"),
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
          ]
        ],

        /*
         * A thematic category
         */
        '_category' => [
          'label'     => _("Category"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => _("Category"),
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
          ]
        ],

        /*
         * who owns it
         */
        '_owner' => [
          'label'     => _("Owner"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => _("Owner"),
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
          ]
        ],

        /*
         * When was it ordered
         */
        '_date_ordered' => [
          'label'     => _("Date ordered"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => _("Date ordered"),
            'type'      => "datefield"
          ]
        ],

        /*
         * when was it received
         */
        '_date_received' => [
          'label'     => _("Date received"),
          'type'      => "string",
          'public'    => false,
          'formData'  => [
            'label'     => _("Date received"),
            'type'      => "datefield"
          ]
        ]
      ]
    );

    $this->addToTypeFields( [
      'type','location','_category','_owner',
      'price',
      '_date_ordered','_date_received'
    ]);
  }
}