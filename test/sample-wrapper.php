<?php
// You can run this if you want to see how long sample.php takes once the PHP
// interpreter is up.
$s = microtime(true);
// This will give you broad compat with Slim's loader if you want to try it
$_SERVER = [
    "REQUEST_METHOD" => "GET",
    "REQUEST_URI" => "/alpha/bravo/charlie/0/echo/golf",
];
require_once "test/sample.php";
$t = microtime(true) - $s;
print "\n" . $t . "\n";
