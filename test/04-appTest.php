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
        $app->run(false, [
            "REQUEST_METHOD" => $method,
            "REQUEST_URI" => $path,
            "QUERY_STRING" => "",
        ]);
        ob_end_flush();
        return $written;
    }
    /**
     * These are the basic things you'd do, including get() and run().
     */
    public function testHandlers() {
        $app = $this
            ->getMockBuilder("\Celery\App")
            ->setMethods(["sendHeaders"])
            ->getMock();

        $saved_greeting = null;
        $saved_headers = null;

        $app->method("sendHeaders")->will(
            $this->returnCallback(function(
                string $greeting,
                array $headers
            ) use (
                &$saved_greeting,
                &$saved_headers
            ) {
                $saved_greeting = $greeting;
                $saved_headers = $headers;
            })
        );
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
        $this->assertRegexp(
            "#^HTTP/1.1 200#",
            $saved_greeting,
            "GET /a: returned a success code"
        );
        $this->assertRegExp(
            "#^Content-Type: application/json#mi",
            implode("\r\n", $saved_headers),
            "GET /a: Headers include a content type (application/json)"
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
        $app = $this
            ->getMockBuilder("\Celery\App")
            ->setMethods(["sendHeaders"])
            ->getMock();

        $app->method("sendHeaders")->will(
            $this->returnCallback(function() {})
        );
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
    /**
     * Tests the stuff that goes in config (handlers for various things).
     */
    public function testConfig() {
        $app = $this
            ->getMockBuilder("\Celery\App")
            ->setMethods(["sendHeaders"])
            ->setConstructorArgs([
                [
                    "errorHandler" => function() {
                        return function(
                            ServerRequestInterface $request,
                            ResponseInterface $response,
                            \Exception $e
                        ) {
                            //error_log($e);
                            return $response->withJson([
                                "type" => "exception",
                            ], 500);
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
                            ], 405);
                        };
                    },
                    "notFoundHandler" => function() {
                        return function(
                            ServerRequestInterface $request,
                            ResponseInterface $response
                        ) {
                            return $response->withJson([
                                "type" => "notfound",
                            ], 404);
                        };
                    },
                    "phpErrorHandler" => function() {
                        return function(
                            ServerRequestInterface $request,
                            ResponseInterface $response,
                            \Error $e
                        ) {
                            //error_log($e);
                            return $response->withJson([
                                "type" => "error",
                            ], 500);
                        };
                    },
                ]
            ])
            ->getMock();


        $saved_greeting = null;
        $saved_headers = null;

        $app->method("sendHeaders")->will(
            $this->returnCallback(function(
                string $greeting,
                array $headers
            ) use (
                &$saved_greeting,
                &$saved_headers
            ) {
                $saved_greeting = $greeting;
                $saved_headers = $headers;
            })
        );
        $app->get("/exception", function() {
            throw new \Exception("foo");
        });
        $app->get("/error", function() {
            $foo = null;
            return $foo->bar();
        });

        $this->assertSame(
            ["type" => "exception"],
            json_decode($this->runRequest($app, "GET", "/exception"), true),
            "errorHandler: works"
        );
        $this->assertRegexp(
            "#^HTTP/1.1 500#",
            $saved_greeting,
            "errorHandler: produces 500 error"
        );
        $this->assertSame(
            ["type" => "error"],
            json_decode($this->runRequest($app, "GET", "/error"), true),
            "phpErrorHandler: works"
        );
        $this->assertRegexp(
            "#^HTTP/1.1 500#",
            $saved_greeting,
            "phpErrorHandler: produces 500 error"
        );
        $this->assertSame(
            ["type" => "notfound"],
            json_decode($this->runRequest($app, "GET", "/notfound"), true),
            "notFoundHandler: works"
        );
        $this->assertRegexp(
            "#^HTTP/1.1 404#",
            $saved_greeting,
            "notFoundHandler: produces 404 error"
        );
        $this->assertSame(
            ["type" => "notallowed", "methods" => ["GET", "HEAD"]],
            json_decode($this->runRequest($app, "POST", "/exception"), true),
            "notAllowedHandler: works"
        );
        $this->assertRegexp(
            "#^HTTP/1.1 405#",
            $saved_greeting,
            "notAllowedHandler: produces 405 error"
        );
    }
    /**
     * Tests the behaviour with no config (where config would override it)
     */
    public function testNoConfig() {
        $app = $this
            ->getMockBuilder("\Celery\App")
            ->setMethods(["sendHeaders"])
            ->getMock();

        $saved_greeting = null;
        $saved_headers = null;

        $app->method("sendHeaders")->will(
            $this->returnCallback(function(
                string $greeting,
                array $headers
            ) use (
                &$saved_greeting,
                &$saved_headers
            ) {
                $saved_greeting = $greeting;
                $saved_headers = $headers;
            })
        );
        $app->get("/exception", function() {
            throw new \Exception("foo");
        });
        $app->get("/error", function() {
            return $foo->bar();
        });

        $this->assertNotEmpty(
            @$this->runRequest($app, "GET", "/exception"),
            "errorHandler (default): works"
        );
        $this->assertRegexp(
            "#^HTTP/1.1 500#",
            $saved_greeting,
            "errorHandler (default): produces 500 error"
        );
        $this->assertNotEmpty(
            @$this->runRequest($app, "GET", "/error"),
            "phpErrorHandler (default): works"
        );
        $this->assertRegexp(
            "#^HTTP/1.1 500#",
            $saved_greeting,
            "phpErrorHandler (default): produces 500 error"
        );
        $this->assertNotEmpty(
            $this->runRequest($app, "GET", "/notfound"),
            "notFoundHandler (default): works"
        );
        $this->assertRegexp(
            "#^HTTP/1.1 404#",
            $saved_greeting,
            "notFoundHandler (default): produces 404 error"
        );
        $this->assertNotEmpty(
            $this->runRequest($app, "POST", "/exception"),
            "notAllowedHandler (default): works"
        );
        $this->assertRegexp(
            "#^HTTP/1.1 405#",
            $saved_greeting,
            "notAllowedHandler (default): produces 405 error"
        );
    }
}
