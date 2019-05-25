<?php
if(count($argv) > 1) {
    error_log("Using {$argv[1]} (from arguments)\n");
} else {
    error_log("Using \Celery\App (default)\n");
}
$app_class = @$argv[1] ?: "Celery\App";
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
