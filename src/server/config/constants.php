<?php

// @todo remove and use yii2 app parameters instead

/**
 * The id of the first migration that the application will use for creating tables on-the-fly
 * (i.e. not using command-line migrations).
 * WARNING: This usage is likely to change, so do not use this constant yet.
 */
if ( ! defined("MIGRATION_ID") )
{
  define( "MIGRATION_ID" ,  "171219_230854" );
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

