<?php

/**
 * A writable directory for temporary files
 */
if ( ! defined( "APP_TMP_PATH" ) )
{
  define( "APP_TMP_PATH", sys_get_temp_dir() );
}

/*
 * The directory where persistent data is stored, for example, the data of persistent
 * objects. Defaults to the system temp path. This is dangerous, however, because
 * this path will be regularly purged and the data is lost. Use a different directory
 * and make it writable.
 */
if ( ! defined("APP_VAR_DIR") )
{
  define( "APP_VAR_DIR" ,  sys_get_temp_dir() );
}

/**
 * The id of the first migration that the application will use for creating tables on-the-fly
 * (i.e. not using command-line migrations).
 * WARNING: This usage is likely to change, so do not use this constant yet.
 */
if ( ! defined("APP_MIGRATION_ID") )
{
  define( "APP_MIGRATION_ID" ,  "m171219_230853" );
}
