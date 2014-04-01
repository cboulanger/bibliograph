<?php

require 'curl.php4';
$curl = new Curl;
$response = $curl->get('localhost:2001');
echo htmlentities($response->toString());
print_r($response->headers);