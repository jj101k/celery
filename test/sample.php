<?php
// This is normally run by test/05-timingTest.php but you can run it yourself
// for testing.
require_once "vendor/autoload.php";
if(!isset($app_class)) {
    $app_class = @$argv[1] ?: "Celery\App";
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
            error_log($e);
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
            error_log($e);
            return $response->withJson([
                "type" => "error",
            ]);
        };
    },
]);
foreach($paths as $path) {
    // Simple example handlers
    $app->get($path, function($request, $response) {
        $response->getBody()->write(file_get_contents("test/example.html"));
        return $response;
    });
    $app->post($path, function($request, $response) {
        //error_log("" . $request->getBody());
        return $response->withJSON([
            "body" => $request->getParsedBody(),
            "file" => array_key_exists("bar", $request->getUploadedFiles()) ?
                "" . $request->getUploadedFiles()["bore"]["bear"][0]->getStream() :
                null,
        ]);
    });
}
$app->run(false);
