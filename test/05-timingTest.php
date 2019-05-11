<?php
/**
 * Tests that the whole lot of work can be done quickly.
 */
class TimingTest extends \PHPUnit\Framework\TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test() {
        ob_start(function($buffer) {return "";});
        $paths = explode("\n", file_get_contents("test/patterns.txt"));

        // Now to do the job
        $s = microtime(true);
        require_once "vendor/autoload.php";
        $app = new \Celery\App([
            "errorHandler" => function() {
                return function(
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    \Exception $e
                ) {
                    return $response->withJson([
                        "type" => "exception",
                    ]);
                };
            },
            "notAllowedHandler" => function() {
                return function(
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    array $methods
                ) {
                    return $response->withJson([
                        "type" => "notallowed",
                        "methods" => $methods,
                    ]);
                };
            },
            "notFoundHandler" => function() {
                return function(
                    ServerRequestInterface $request,
                    ResponseInterface $response
                ) {
                    return $response->withJson([
                        "type" => "notfound",
                    ]);
                };
            },
            "phpErrorHandler" => function() {
                return function(
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    \Error $e
                ) {
                    return $response->withJson([
                        "type" => "error",
                    ]);
                };
            },
        ]);
        foreach($paths as $path) {
            // Simple example handlers
            $app->get($path, function($request, $response) {
                return $response->withJSON([]);
            });
        }
        $app->run([
            "REQUEST_METHOD" => "GET",
            "REQUEST_URI" => $paths[count($paths) - 1],
        ]);
        $r = microtime(true) - $s;
        // All done!

        ob_end_flush();
        $this->assertLessThan(
            0.004,
            $r,
            "All handling complete in < 4ms"
        );
    }
}
