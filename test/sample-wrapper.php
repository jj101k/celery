<?php
if(count($argv) > 1) {
    error_log("Using {$argv[1]} (from arguments)\n");
} else {
    error_log("Using \Celery\App (default)\n");
}
$app_class = @$argv[1] ?: "Celery\App";
$use_post = !!@$argv[2];
if($use_post) {
    $_SERVER = [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI" => "/alpha/bravo/charlie/0/echo/golf",
        "QUERY_STRING" => "",
        "CONTENT_TYPE" => "application/json",
    ];
} else {
    $_SERVER = [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/alpha/bravo/charlie/0/echo/golf",
        "QUERY_STRING" => "",
    ];
}
// You can run this if you want to see how long sample.php takes once the PHP
// interpreter is up.
$s = microtime(true);
require_once "test/sample.php";
$t = microtime(true) - $s;
print "\n" . $t . "\n";
