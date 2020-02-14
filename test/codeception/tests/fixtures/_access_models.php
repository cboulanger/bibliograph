<?php

namespace tests\fixtures;

return [
  'user' => [
    'class' => UserFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/user.php',
  ],
  'group' => [
    'class' => GroupFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/group.php',
  ],
  'role' => [
    'class' => RoleFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/role.php',
  ],
  'permission' => [
    'class' => PermissionFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/permission.php',
  ],
  'config' => [
    'class' => ConfigFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/config.php',
  ],
  'userconfig' => [
    'class' => UserConfigFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/userconfig.php',
  ],
  'session' => [
    'class' => SessionFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/session.php',
  ],
  'datasource' => [
    'class' => DatasourceFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/datasource.php',
  ],
  'datasource_group' => [
    'class' => DatasourceGroupFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/datasource_group.php',
  ],
  'datasource_role' => [
    'class' => DatasourceRoleFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/datasource_role.php',
  ],
  'datasource_user' => [
    'class' => DatasourceUserFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/datasource_user.php',
  ],
  'group_user' => [
    'class' => GroupUserFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/group_user.php',
  ],
  'permission_role' => [
    'class' => PermissionRoleFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/permission_role.php',
  ],
  'schema' => [
    'class' => SchemaFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/schema.php',
  ],
  'role_schema' => [
    'class' => RoleSchemaFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/role_schema.php',
  ],
  'user_role' => [
    'class' => UserRoleFixture::class,
    'dataFile' => APP_TESTS_DIR . '/_data/user_role.php',
  ],
];
