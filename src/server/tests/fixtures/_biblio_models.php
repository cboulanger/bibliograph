<?php
return [
  'reference' => [
      'class' => \tests\fixtures\ReferenceFixture::className(),
      'dataFile' => '@tests/_data/reference.php',
      'tableName' => 'database1_data_Reference'
  ],
  'folder' => [
    'class' => \tests\fixtures\FolderFixture::className(),
    'dataFile' => '@tests/_data/folder.php',
    'tableName' => 'database1_data_Folder'
  ],
  'folder_reference' => [
    'class' => \tests\fixtures\FolderReferenceFixture::className(),
    'dataFile' => '@tests/_data/folder_reference.php',
    'tableName' => 'database1_join_Folder_Reference'
  ], 
  'datasource' => [
    'class' => \tests\fixtures\DatasourceFixture::className(),
    'dataFile' => '@tests/_data/datasource.php',
  ],  
];