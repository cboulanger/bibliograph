<?php

// @todo remove and use yii2 app parameters instead

if ( ! defined("APP_INI_FILE") )
{
  define( "APP_INI_FILE" ,  __DIR__ . "/../bibliograph.ini.php" );
}

/*
 * the timeout of a normal session, in seconds, Defaults to 60 minutes
 */
if ( ! defined('ACCESS_TIMEOUT') )
{
  define('ACCESS_TIMEOUT', 60*60 );
}

/*
 * The lifetime of an anonymous user session (if not refreshed), in seconds. Defaults to 1 minute.
 */
if ( ! defined("ACCESS_ANONYMOUS_SESSION_LIFETIME") )
{
  define( "ACCESS_ANONYMOUS_SESSION_LIFETIME" , 60 );
}

/*
 * The lifetime of a token (a distributable session id), in seconds. Defaults to 24h
 * todo not yet used, implement
 */
if ( ! defined("ACCESS_TOKEN_LIFETIME") )
{
  define( "ACCESS_TOKEN_LIFETIME" , 60*60*24 );
}

/*
 * the salt used for storing encrypted passwords
 */
defined('ACCESS_SALT_LENGTH') or define('ACCESS_SALT_LENGTH', 9);

/**
 * The character to separate values in a database field
 * @todo move into preference
 */
defined('BIBLIOGRAPH_VALUE_SEPARATOR') or define('BIBLIOGRAPH_VALUE_SEPARATOR', ";");


/**
 * The path to the bibutils executables. Must contain a trailing slash
 * since the exeuctable name is appended to the path. If not defined,
 * the bibutils executables must be on the PATH
 */
if( !defined('BIBUTILS_PATH') )
{
  define('BIBUTILS_PATH','');
}


