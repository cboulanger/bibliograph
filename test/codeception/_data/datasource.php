<?php

return [
  [
    // can be accessed by members of group 1
    'id' => 1,
    'namedId' => 'database1',
    'title' => 'Database 1',
    'description' => null,
    'schema' => 'bibliograph_datasource',
    'type' => 'mysql',
    'host' => null,
    'port' => null,
    'database' => 'tests',
    'username' => null,
    'password' => null,
    'encoding' => null,
    'prefix' => 'database1_',
    'resourcepath' => null,
    'active' => 1,
    'readonly' => 0,
    'hidden' => 0,
  ],
  [
    // can be accessed by members of group 2 and 3
    'id' => 2,
    'namedId' => 'database2',
    'title' => 'Database 2',
    'description' => null,
    'schema' => 'bibliograph_datasource',
    'type' => 'mysql',
    'host' => null,
    'port' => null,
    'database' => 'tests',
    'username' => null,
    'password' => null,
    'encoding' => null,
    'prefix' => 'database2_',
    'resourcepath' => null,
    'active' => 1,
    'readonly' => 0,
    'hidden' => 0,
  ],
  [
    // access by jessica jones only
    'id' => 3,
    'namedId' => 'jessica',
    'title' => 'Jessica\'s group',
    'description' => null,
    'schema' => 'bibliograph_datasource',
    'type' => 'mysql',
    'host' => null,
    'port' => null,
    'database' => 'tests',
    'username' => null,
    'password' => null,
    'encoding' => null,
    'prefix' => 'jessica_',
    'resourcepath' => null,
    'active' => 1,
    'readonly' => 0,
    'hidden' => 0,
  ]
];
