<?php

/*
 * YII debug mode: true/false
 */
defined("YII_DEBUG") or define( "YII_DEBUG" , isset($_SERVER['YII_DEBUG'])?$_SERVER['YII_DEBUG']:false);

/*
 * YII environment: prod/dev/test
 */
defined("YII_ENV") or define( "YII_ENV", isset($_SERVER['YII_ENV']) ? $_SERVER['YII_ENV'] : 'prod');

/*
 * The path to the root directory containing the complete project
 */
defined("APP_ROOT_DIR") or define( "APP_ROOT_DIR" , __DIR__ . "/../../..");

/*
 * The path to the directory in which the log files should be saved
 */
defined("APP_LOG_DIR") or define( "APP_LOG_DIR" , '@runtime/logs');

/*
 * The name of the logfile
 */
defined("APP_LOG_NAME") or define( "APP_LOG_NAME" , 'app.log');

/*
 * The path to the file containing server environment variable definitions
 */
defined("DOTENV_FILE") or define( "DOTENV_FILE" , YII_ENV === "test" ? APP_ROOT_DIR . "/test/.env" : APP_ROOT_DIR . "/.env");

/*
 * The path to the directory with the backend code
 */
defined("APP_BACKEND_DIR") or define( "APP_BACKEND_DIR" , __DIR__ . "/..");

/*
 * The path to the directory with the frontend code
 */
defined("APP_FRONTEND_DIR") or define( "APP_FRONTEND_DIR" , APP_ROOT_DIR . "/src/client/bibliograph");

/*
 * The path to the application configuration file (as distinct from the YII application configuration)
 */
defined("APP_CONFIG_FILE") or define( "APP_CONFIG_FILE" , APP_ROOT_DIR . "/app.conf.toml");

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


/**
 * Codecdeption tests do not pass the Bearer Authentication Header correctly,
 * set true for a workaround
 */
defined('JSON_RPC_USE_PAYLOAD_TOKEN_AUTH') or define('JSON_RPC_USE_PAYLOAD_TOKEN_AUTH', false);

/**
 * The time in seconds the execution of a request is allowed to last before a response is returned
 * that will trigger a new request to handle the remaining tasks
 */
defined('REQUEST_EXECUTION_THRESHOLD') or define('REQUEST_EXECUTION_THRESHOLD', 5);

/**
 * The time in seconds the execution of a request is allowed to last before it times out
 * Will be set as set_time_limit();
 */
defined('MAX_EXECUTION_TIME') or define('MAX_EXECUTION_TIME', 120);

/**
 * The debug levels
 */
if (!defined("YII_DEBUG_LEVELS")) {
  if (isset($_SERVER['YII_DEBUG_LEVELS'])) {
    define("YII_DEBUG_LEVELS", $_SERVER['YII_DEBUG_LEVELS']);
  } else if (YII_DEBUG) {
    define("YII_DEBUG_LEVELS", ['trace','info', 'warning']);
  } else {
    define("YII_DEBUG_LEVELS", ['info', 'warning']);
  }
}

/**
 * Debug categories
 */
if (!defined("YII_DEBUG_CATEGORIES")) {
  if (isset($_SERVER['YII_DEBUG_CATEGORIES'])) {
    define("YII_DEBUG_CATEGORIES", $_SERVER['YII_DEBUG_CATEGORIES']);
  } else if (YII_DEBUG) {
    define("YII_DEBUG_CATEGORIES", ['access','setup', 'app*', 'plugin*', 'jsonrpc', "debug", "yii\\web\\User*", "yii\\base\\Module*"]);
  } else {
    define("YII_DEBUG_CATEGORIES", ['app*', 'plugin*', 'jsonrpc']);
  }
}
