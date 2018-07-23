<?php
namespace tests\fixtures;
return [
  'reference' => [
    'class' => ReferenceFixture::class,
    'dataFile' => '@tests/_data/reference.php',
    'tableName' => 'database1_data_Reference'
  ],
  'folder' => [
    'class' => FolderFixture::class,
    'dataFile' => '@tests/_data/folder.php',
    'tableName' => 'database1_data_Folder'
  ],
  'folder_reference' => [
    'class' => FolderReferenceFixture::class,
    'dataFile' => '@tests/_data/folder_reference.php',
    'tableName' => 'database1_join_Folder_Reference'
  ],
];