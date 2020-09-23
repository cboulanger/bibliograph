<?php
namespace tests\fixtures;
return [
  'reference' => [
    'class' => ReferenceFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/reference.php',
    'tableName' => 'database1_data_Reference'
  ],
  'folder' => [
    'class' => FolderFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/folder.php',
    'tableName' => 'database1_data_Folder'
  ],
  'folder_reference' => [
    'class' => FolderReferenceFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/folder_reference.php',
    'tableName' => 'database1_join_Folder_Reference'
  ],
];
