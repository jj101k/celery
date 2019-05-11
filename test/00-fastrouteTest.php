<?php
require_once "vendor/autoload.php";
/**
 * Tests that the fastroute<->regexp pattern support works
 */
class FastrouteTest extends \PHPUnit\Framework\TestCase {
    /**
     * Just tests that the regular expressions match against the patterns, after
     * trivial tweaks
     */
    public function testMatch() {
        $paths = explode("\n", file_get_contents("test/patterns.txt"));
        foreach($paths as $path) {
            $regexp = \Celery\App::fastrouteToRegexp($path);
            $simple_path = preg_replace(
                "/[{]\w+:[^}]+[}]/",
                "0",
                preg_replace(
                    "/[{]\w+:(\w+)[^}]+[}]/",
                    "\\1",
                    $path
                )
            );
            $this->assertTrue(
                !!preg_match($regexp, $simple_path),
                "{$path} produces a regexp which matches {$simple_path}"
            );
        }
    }
    /**
     * Makes sure all patterns are reversible
     */
    public function testReversible() {
        $paths = explode("\n", file_get_contents("test/patterns.txt"));
        foreach($paths as $path) {
            $this->assertSame(
                $path,
                \Celery\App::regexpToFastRoute(\Celery\App::fastrouteToRegexp($path)),
                "regexpToFastRoute reverses fastrouteToRegexp"
            );
        }
    }
    /**
     * Baseline speed test. If this misses, it might just be on a slow machine -
     * if this happens regularly please report it.
     */
    public function testSpeed() {
        $paths = explode("\n", file_get_contents("test/patterns.txt"));
        $t = microtime(true);
        foreach($paths as $path) {
            \Celery\App::fastrouteToRegexp($path);
        }
        $re_time = microtime(true) - $t;
        $this->assertLessThan(
            0.000050 * count($paths),
            $re_time,
            "Takes less than ~50us per path"
        );
    }
    /**
     * Comparison to Fastroute parsing. This should be similar.
     */
    public function testSpeedLikeFastRoute() {
        $parser = new \FastRoute\RouteParser\Std();
        $paths = explode("\n", file_get_contents("test/patterns.txt"));

        $s = microtime(true);
        foreach($paths as $path) {
            $parser->parse($path);
        }
        $fastroute_time = microtime(true) - $s;

        $s = microtime(true);
        foreach($paths as $path) {
            \Celery\App::fastrouteToRegexp($path);
        }
        $re_time = microtime(true) - $s;

        $this->assertLessThan(
            $fastroute_time * 1.5,
            $re_time,
            "Takes similar time to fastroute parsing"
        );
    }
}