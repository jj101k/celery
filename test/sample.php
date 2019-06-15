<?php
// This is normally run by test/05-timingTest.php but you can run it yourself
// for testing.
require_once "vendor/autoload.php";
if(!isset($app_class)) {
    $app_class = @$argv[1] ?: "Celery\App";
    $use_post = !!@$argv[2];
}
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
$paths = explode("\n", file_get_contents("test/patterns.txt"));
$app = new $app_class([
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
    $app->post($path, function($request, $response) {
        //error_log("" . $request->getBody());
        return $response->withJSON($request->getParsedBody());
    });
}

if($use_post) {
    // This will give you broad compat with Slim's loader if you want to try it
    $_SERVER = [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI" => "/alpha/bravo/charlie/0/echo/golf",
        "QUERY_STRING" => "",
        "CONTENT_TYPE" => "application/json",
    ];
    $app->run(false, [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI" => "/alpha/bravo/charlie/0/echo/golf",
        "QUERY_STRING" => "",
        "CONTENT_TYPE" => "application/json",
    ]);
} else {
    // This will give you broad compat with Slim's loader if you want to try it
    $_SERVER = [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/alpha/bravo/charlie/0/echo/golf",
        "QUERY_STRING" => "",
    ];
    $app->run(false, [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/alpha/bravo/charlie/0/echo/golf",
        "QUERY_STRING" => "",
    ]);
}
