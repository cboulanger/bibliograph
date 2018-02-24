<?php
return [
  'user' => [
    'class' => \tests\fixtures\UserFixture::class,
    'dataFile' => '@tests/_data/user.php',
  ],
  'group' => [
    'class' => \tests\fixtures\GroupFixture::class,
    'dataFile' => '@tests/_data/group.php',
  ],
  'role' => [
    'class' => \tests\fixtures\RoleFixture::class,
    'dataFile' => '@tests/_data/role.php',
  ],
  'permission' => [
      'class' => \tests\fixtures\PermissionFixture::class,
      'dataFile' => '@tests/_data/permission.php',
  ],
  'config' => [
      'class' => \tests\fixtures\ConfigFixture::class,
      'dataFile' => '@tests/_data/config.php',
  ],
  'userconfig' => [
    'class' => \tests\fixtures\UserConfigFixture::class,
    'dataFile' => '@tests/_data/userconfig.php',
  ],
  'session' => [
    'class' => \tests\fixtures\SessionFixture::class,
    'dataFile' => '@tests/_data/session.php',
  ],  
  'datasource' => [
    'class' => \tests\fixtures\DatasourceFixture::class,
    'dataFile' => '@tests/_data/datasource.php',
  ],
  'datasource_group' => [
      'class' => \tests\fixtures\DatasourceGroupFixture::class,
      'dataFile' => '@tests/_data/datasource_group.php',
  ],
  'datasource_role' => [
      'class' => \tests\fixtures\DatasourceRoleFixture::class,
      'dataFile' => '@tests/_data/datasource_role.php',
  ],
  'datasource_user' => [
      'class' => \tests\fixtures\DatasourceUserFixture::class,
      'dataFile' => '@tests/_data/datasource_user.php',
  ],
  'group_user' => [
      'class' => \tests\fixtures\GroupUserFixture::class,
      'dataFile' => '@tests/_data/group_user.php',
  ],
  'permission_role' => [
      'class' => \tests\fixtures\PermissionRoleFixture::class,
      'dataFile' => '@tests/_data/permission_role.php',
  ],
  'schema' => [
    'class' => \tests\fixtures\SchemaFixture::class,
    'dataFile' => '@tests/_data/schema.php',
  ],
  'role_schema' => [
    'class' => \tests\fixtures\RoleSchemaFixture::class,
    'dataFile' => '@tests/_data/role_schema.php',
  ],
  'user_role' => [
      'class' => \tests\fixtures\UserRoleFixture::class,
      'dataFile' => '@tests/_data/user_role.php',
  ],
];
