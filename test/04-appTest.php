<?php
require_once "vendor/autoload.php";
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
/**
 * Tests the main app object for Slim compatibility
 */
class AppTest extends \PHPUnit\Framework\TestCase {
    /**
     * Runs the request via $app, returns the body content.
     *
     * @param \Celery\App $app
     * @param string $method
     * @param string $path
     * @return string
     */
    private function runRequest(\Celery\App $app, string $method, string $path): string {
        $written = "";
        ob_start(function($buffer) use (&$written) {
            $written .= $buffer;
            return "";
        });
        $app->run([
            "REQUEST_METHOD" => $method,
            "REQUEST_URI" => $path,
        ]);
        ob_end_flush();
        return $written;
    }
    /**
     * These are the basic things you'd do, including get() and run().
     */
    public function testHandlers() {
        $app = new \Celery\App();
        $handler_for = function(string $method) {
            return function(
                ServerRequestInterface $req,
                ResponseInterface $res,
                array $args
            ) use (
                $method
            ) {
                return $res->withJSON([
                    "method" => $method,
                    "args" => $args,
                ]);
            };
        };

        $TEST_METHODS = [
            "delete",
            "get",
            "head",
            "options",
            "post",
            "put",
        ];

        $app->any("/a", $handler_for("any"));

        foreach($TEST_METHODS as $method) {
            $app->$method("/b/{c}", $handler_for($method));
        }

        $app->group("/c", function($app) use ($handler_for) {
            $app->get("", $handler_for("c-get"));
            $app->get("/d", $handler_for("c-d-get"));
        });
        $app->map(["GET", "OPTIONS"], "/e[/{f}]", $handler_for("map"));

        $this->assertSame(
            ["method" => "any", "args" => []],
            json_decode($this->runRequest($app, "GET", "/a"), true),
            "GET /a: as expected"
        );
        foreach($TEST_METHODS as $method) {
            $http_method = strtoupper($method);
            $this->assertSame(
                ["method" => $method, "args" => ["c" => "see"]],
                json_decode($this->runRequest($app, $http_method, "/b/see"), true),
                "{$http_method} /b/see: as expected"
            );
        }
        $this->assertSame(
            ["method" => "c-get", "args" => []],
            json_decode($this->runRequest($app, "GET", "/c"), true),
            "GET /c: as expected"
        );
        $this->assertSame(
            ["method" => "c-d-get", "args" => []],
            json_decode($this->runRequest($app, "GET", "/c/d"), true),
            "GET /c/d: as expected"
        );
        $this->assertSame(
            ["method" => "map", "args" => []],
            json_decode($this->runRequest($app, "GET", "/e"), true),
            "GET /e: as expected"
        );
        $this->assertSame(
            ["method" => "map", "args" => ["f" => "eff"]],
            json_decode($this->runRequest($app, "OPTIONS", "/e/eff"), true),
            "OPTIONS /e/eff: as expected"
        );

        $this->assertSame(
            ["method" => "map", "args" => []],
            json_decode($this->runRequest($app, "HEAD", "/e"), true),
            "HEAD /e: works implicitly via GET"
        );
    }

    /**
     * This is an approximation of the Slim tutorial
     */
    public function testHelloWorld() {
        $app = new \Celery\App();
        $app->get(
            "/hello/{name}",
            function(
                ServerRequestInterface $request,
                ResponseInterface $response,
                array $args
            ) {
                $response->getBody()->write("Hello {$args["name"]}");

                return $response;
            }
        );
        $this->assertSame(
            "Hello world",
            $this->runRequest($app, "GET", "/hello/world"),
            "Simple body worked as expected"
        );
    }
}
