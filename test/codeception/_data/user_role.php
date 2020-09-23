<?php

return [
  [
    // admin is admin in all groups
    'UserId' => 1,
    'RoleId' => 1,
    'GroupId' => null,
  ],
  [
    // Don Draper is user in all groups to which he belongs
    'UserId' => 3,
    'RoleId' => 4,
    'GroupId' => null,
  ],
  [
    // Jessica Jones is user in group 1
    'UserId' => 5,
    'RoleId' => 4,
    'GroupId' => 1,
  ],
  [
    // Frank Underwood is manager in all groups to which he belongs
    'UserId' => 6,
    'RoleId' => 3,
    'GroupId' => null,
  ],
  [
    // Sarah Manning is user in all groups to which she belongs
    'UserId' => 7,
    'RoleId' => 4,
    'GroupId' => null
  ],
  [
    // Sarah Manning is manager in group 3
    'UserId' => 7,
    'RoleId' => 3,
    'GroupId' => 3,
  ],
  [
    // Dale Cooper is manager in group 2
    'UserId' => 4,
    'RoleId' => 3,
    'GroupId' => 2,
  ],
];