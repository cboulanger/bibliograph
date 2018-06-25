<?php

// @todo remove and use yii2 app parameters instead
defined("APP_CONFIG_FILE") or define( "APP_CONFIG_FILE" , __DIR__ . "/../app.conf.toml");

/*
 * the timeout of a normal session, in seconds, Defaults to 60 minutes
 */
defined('ACCESS_TIMEOUT')  or define('ACCESS_TIMEOUT', 60*60 );

/*
 * The lifetime of an anonymous user session (if not refreshed), in seconds. Defaults to 1 minute.
 */
defined("ACCESS_ANONYMOUS_SESSION_LIFETIME") or define( "ACCESS_ANONYMOUS_SESSION_LIFETIME" , 60 );

/*
 * The lifetime of a token (a distributable session id), in seconds. Defaults to 24h
 * todo not yet used, implement
 */
defined("ACCESS_TOKEN_LIFETIME") or define( "ACCESS_TOKEN_LIFETIME" , 60*60*24 );

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
 * The path to a writable directory where temporary files are stored
 * Defaults to the system temporary directory
 */
defined('TMP_PATH') or define('TMP_PATH', sys_get_temp_dir());
