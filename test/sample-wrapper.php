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
require_once "test/sample.php";
$t = microtime(true) - $s;
print "\n" . $t . "\n";
