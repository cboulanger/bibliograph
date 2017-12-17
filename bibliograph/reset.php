<?php
/*
 * reset caches, session, cookies
 */
require "services/config/server.conf.php";
session_destroy();
unlink( QCL_LOG_FILE );
unlink ( QCL_VAR_DIR . "/bibliograph.dat" );
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time()-1000);
        setcookie($name, '', time()-1000, '/');
    }
}
echo "<h1>Bibliograph has been reset.</h1>";
echo '<p>Return to <a href="source/">source</a> or <a href="build/">build</a> version.</p>';