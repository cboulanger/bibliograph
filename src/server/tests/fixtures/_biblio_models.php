<?php
return [
  'reference' => [
      'class' => \tests\fixtures\ReferenceFixture::className(),
      'dataFile' => '@tests/_data/reference.php',
  ],
  'folder' => [
    'class' => \tests\fixtures\FolderFixture::className(),
    'dataFile' => '@tests/_data/folder.php',
  ],
  'folder_reference' => [
    'class' => \tests\fixtures\FolderReferenceFixture::className(),
    'dataFile' => '@tests/_data/folder_reference.php',
  ], 
  'datasource' => [
    'class' => \tests\fixtures\DatasourceFixture::className(),
    'dataFile' => '@tests/_data/datasource.php',
  ],  
];