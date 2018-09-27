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
  'app.access.userdatabase.roles' => [
    'type' => "list",
    'default' => "manager,user"
  ],
  'ldap.enabled' => [
    'type' => "boolean",
    'default' => false
  ]
];