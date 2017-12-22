<?php
return [
  'user' => [
    'class' => \tests\fixtures\UserFixture::className(),
    'dataFile' => '@tests/_data/user.php',
  ],
  'group' => [
    'class' => \tests\fixtures\GroupFixture::className(),
    'dataFile' => '@tests/_data/group.php',
  ],
  'role' => [
    'class' => \tests\fixtures\RoleFixture::className(),
    'dataFile' => '@tests/_data/role.php',
  ],
  'permission' => [
      'class' => \tests\fixtures\PermissionFixture::className(),
      'dataFile' => '@tests/_data/permission.php',
  ],
  'config' => [
      'class' => \tests\fixtures\ConfigFixture::className(),
      'dataFile' => '@tests/_data/config.php',
  ],
  'session' => [
    'class' => \tests\fixtures\SessionFixture::className(),
    'dataFile' => '@tests/_data/session.php',
  ],  
  'datasource' => [
    'class' => \tests\fixtures\DatasourceFixture::className(),
    'dataFile' => '@tests/_data/datasource.php',
  ],    
  'datasourceschema' => [
    'class' => \tests\fixtures\DatasourceSchemaFixture::className(),
    'dataFile' => '@tests/_data/datasource_schema.php',
  ],  
  'datasource_group' => [
      'class' => \tests\fixtures\DatasourceGroupFixture::className(),
      'dataFile' => '@tests/_data/datasource_group.php',
  ],
  'datasource_role' => [
      'class' => \tests\fixtures\DatasourceRoleFixture::className(),
      'dataFile' => '@tests/_data/datasource_role.php',
  ],
  'datasource_user' => [
      'class' => \tests\fixtures\DatasourceUserFixture::className(),
      'dataFile' => '@tests/_data/datasource_user.php',
  ],
  'group_user' => [
      'class' => \tests\fixtures\GroupUserFixture::className(),
      'dataFile' => '@tests/_data/group_user.php',
  ],
  'permission_role' => [
      'class' => \tests\fixtures\PermissionRoleFixture::className(),
      'dataFile' => '@tests/_data/permission_role.php',
  ],
  'user_role' => [
      'class' => \tests\fixtures\UserRoleFixture::className(),
      'dataFile' => '@tests/_data/user_role.php',
  ],
];
