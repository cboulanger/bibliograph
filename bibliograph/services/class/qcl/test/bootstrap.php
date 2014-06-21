<?php
ini_set('include_path', implode(
  PATH_SEPARATOR,
  array(
    dirname( dirname(__DIR__) ),
    ini_get("include_path")
  )
) );
require_once dirname(__DIR__) . "/bootstrap.php";