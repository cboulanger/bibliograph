<?php
namespace tests\fixtures;

return [
  'user' => [
    'class' => UserFixture::class,
    'dataFile' => '@tests/_data/user.php',
  ],
  'group' => [
    'class' => GroupFixture::class,
    'dataFile' => '@tests/_data/group.php',
  ],
  'role' => [
    'class' => RoleFixture::class,
    'dataFile' => '@tests/_data/role.php',
  ],
  'permission' => [
      'class' => PermissionFixture::class,
      'dataFile' => '@tests/_data/permission.php',
  ],
  'config' => [
      'class' => ConfigFixture::class,
      'dataFile' => '@tests/_data/config.php',
  ],
  'userconfig' => [
    'class' => UserConfigFixture::class,
    'dataFile' => '@tests/_data/userconfig.php',
  ],
  'session' => [
    'class' => SessionFixture::class,
    'dataFile' => '@tests/_data/session.php',
  ],  
  'datasource' => [
    'class' => DatasourceFixture::class,
    'dataFile' => '@tests/_data/datasource.php',
  ],
  'datasource_group' => [
      'class' => DatasourceGroupFixture::class,
      'dataFile' => '@tests/_data/datasource_group.php',
  ],
  'datasource_role' => [
      'class' => DatasourceRoleFixture::class,
      'dataFile' => '@tests/_data/datasource_role.php',
  ],
  'datasource_user' => [
      'class' => DatasourceUserFixture::class,
      'dataFile' => '@tests/_data/datasource_user.php',
  ],
  'group_user' => [
      'class' => GroupUserFixture::class,
      'dataFile' => '@tests/_data/group_user.php',
  ],
  'permission_role' => [
      'class' => PermissionRoleFixture::class,
      'dataFile' => '@tests/_data/permission_role.php',
  ],
  'schema' => [
    'class' => SchemaFixture::class,
    'dataFile' => '@tests/_data/schema.php',
  ],
  'role_schema' => [
    'class' => RoleSchemaFixture::class,
    'dataFile' => '@tests/_data/role_schema.php',
  ],
  'user_role' => [
      'class' => UserRoleFixture::class,
      'dataFile' => '@tests/_data/user_role.php',
  ],
];