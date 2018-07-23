<?php

return [
  [
    // group 1: jessica_jones, sarah_manning, frank_underwood (global manager), access to datasource 1
    'id' => 1,
    'namedId' => 'group1',
    'name' => 'Group 1',
    'description' => null,
    'ldap' => 0,
    'defaultRole' => 'user',
    'active' => 1,
  ],
  [
    // group 2: frank_underwood (global manager), dale_cooper (local manager), sarah_manning, access to datasource2
    'id' => 2,
    'namedId' => 'group2',
    'name' => 'Group 2',
    'description' => null,
    'ldap' => 0,
    'defaultRole' => 'user',
    'active' => 1,
  ],
  [
    // group 3: dale_cooper, don_draper, sarah_manning (local manager), access to datasource2
    'id' => 3,
    'namedId' => 'group3',
    'name' => 'Group 3',
    'description' => null,
    'ldap' => 0,
    'defaultRole' => 'user',
    'active' => 1,
  ],
];