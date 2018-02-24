<?php
return [
  'app.datasource.baseschema' => [
    'type' => "string",
    'default' => "bibliograph"
  ],
  'app.datasource.baseclass' => [
    'type' => "string",
    'default' => \app\models\BibliographicDatasource::class
  ]
];