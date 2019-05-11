<?php
require_once "vendor/autoload.php";
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
/**
 * Tests the main app object for Slim compatibility
 */
class AppTest extends \PHPUnit\Framework\TestCase {
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

        $r = function(string $method, string $path) use ($app) {
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
        };
        $this->assertSame(
            ["method" => "any", "args" => []],
            json_decode($r("GET", "/a"), true),
            "GET /a: as expected"
        );
        foreach($TEST_METHODS as $method) {
            $http_method = strtoupper($method);
            $this->assertSame(
                ["method" => $method, "args" => ["c" => "see"]],
                json_decode($r($http_method, "/b/see"), true),
                "{$http_method} /b/see: as expected"
            );
        }
        $this->assertSame(
            ["method" => "c-get", "args" => []],
            json_decode($r("GET", "/c"), true),
            "GET /c: as expected"
        );
        $this->assertSame(
            ["method" => "c-d-get", "args" => []],
            json_decode($r("GET", "/c/d"), true),
            "GET /c/d: as expected"
        );
        $this->assertSame(
            ["method" => "map", "args" => []],
            json_decode($r("GET", "/e"), true),
            "GET /e: as expected"
        );
        $this->assertSame(
            ["method" => "map", "args" => ["f" => "eff"]],
            json_decode($r("OPTIONS", "/e/eff"), true),
            "OPTIONS /e/eff: as expected"
        );

        $this->assertSame(
            ["method" => "map", "args" => []],
            json_decode($r("HEAD", "/e"), true),
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
        $written = "";
        ob_start(function($buffer) use (&$written) {
            $written .= $buffer;
            return "";
        });
        $app->run([
            "REQUEST_METHOD" => "GET",
            "REQUEST_URI" => "/hello/world",
        ]);
        ob_end_flush();
        $this->assertSame(
            "Hello world",
            $written,
            "Simple body worked as expected"
        );
    }
}
