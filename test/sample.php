<?php
// This is normally run by test/05-timingTest.php but you can run it yourself
// for testing.
require_once "vendor/autoload.php";
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
$paths = explode("\n", file_get_contents("test/patterns.txt"));
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
$app->run(false, [
    "REQUEST_METHOD" => "GET",
    "REQUEST_URI" => "/alpha/bravo/charlie/0/echo/golf",
]);
