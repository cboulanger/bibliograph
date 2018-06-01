<?php
return [
  'app.datasource.baseschema' => [
    'type' => "string",
    'default' => \app\controllers\SetupController::DATASOURCE_DEFAULT_SCHEMA
  ],
  'app.datasource.baseclass' => [
    'type' => "string",
    'default' => \app\models\BibliographicDatasource::class
  ],
  'app.clipboard.content-type' => [
    'type' => "string",
    'default' => ""
  ],
  'app.clipboard.content' => [
    'type' => "string",
    'default' => ""
  ]
];