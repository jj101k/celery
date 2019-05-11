<?php
// You can run this if you want to see how long sample.php takes once the PHP
// interpreter is up.
$s = microtime(true);
require_once "test/sample.php";
$t = microtime(true) - $s;
print "\n" . $t . "\n";