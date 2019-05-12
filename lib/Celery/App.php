<?php
namespace Celery;
/**
 * The main entry point for an HTTP service. This should be broadly compatibile
 * with \Slim\App
 */
class App {
    /**
     * @property array See __construct()
     */
    private $config;

    /**
     * @property array Path patterns to methods to handler callables
     */
    private $handlers = [];

    /**
     * @property string Used for group().
     */
    private $pathPrefix = "";

    /**
     * @param string $pattern A FastRoute pattern, eg. /foo/{bar}[/{baz:.*}]
     * @return string An equivalent regexp eg. "/foo/(?<bar>[^/]+)(?:/(?<baz>.+))?"
     */
    private static function fastrouteToRegexpComponent(string $pattern): string {
        $pattern_out = "";
        if(preg_match("/^((?:[^[{]*[{][^}]+[}])*[^[{]*)(.*)/", $pattern, $md) and $md[1] != "") {
            $o_nonoptional_hunk = $md[1];
            $nonoptional_hunk = $o_nonoptional_hunk;
            $pattern = $md[2];
            while($nonoptional_hunk) {
                if(preg_match("/^[{]([^}:]+)(?::([^}]*))?[}](.*)/", $nonoptional_hunk, $md)) {
                    $match = $md[2] ?: "[^/]+";
                    $pattern_out .= "(?<{$md[1]}>{$match})";
                    $nonoptional_hunk = $md[3];
                } elseif(preg_match("/^([^{]+)(.*)/", $nonoptional_hunk, $md)) {
                    $pattern_out .= quotemeta($md[1]);
                    $nonoptional_hunk = $md[2];
                } else {
                    throw new \RuntimeException("Error parsing fastroute expression at: {$nonoptional_hunk}");
                }
            }
        }
        if(preg_match("/^\[(.*)\]$/", $pattern, $md)) {
            $pattern_out .= "(?:" . self::fastrouteToRegexpComponent($md[1]) . ")?";
        } elseif($pattern != "") {
            throw new \RuntimeException("Error parsing fastroute expression at: {$pattern}");
        }
        return $pattern_out;
    }

    /**
     * Produces a regular expression for the fastroute pattern. This is intended
     * to make it predictable to map URLs to endpoints.
     *
     * @param string $pattern A FastRoute pattern, eg. /foo/{bar}[/{baz:.*}]
     * @return string An equivalent regexp eg. "#^/foo/(?<bar>[^/]+)(?:/(?<baz>.+))?$#"
     */
    public static function fastrouteToRegexp(string $pattern): string {
        return "#^" . self::fastrouteToRegexpComponent($pattern) . "$#";
    }

    /**
     * This reverses the fastrouteToRegexp() transform. It's not guaranteed to
     * work with any other regexps.
     *
     * @param string $pattern A regexp eg. "#^/foo/(?<bar>[^/]+)(?:/(?<baz>.+))?$#"
     * @return string An equivalent FastRoute pattern, eg. /foo/{bar}[/{baz:.*}]
     */
    public static function regexpToFastroute(string $pattern): string {
        $stripped = substr($pattern, 2, strlen($pattern) - 4);
        $with_vars = preg_replace(
            "/[(][?]<(\w+)>([^)]*)[)]/",
            "{\\1:\\2}",
            $stripped
        );
        $with_vars_simple = preg_replace(
            "#[{](\w+):" . quotemeta("[^/]+") . "[}]#",
            "{\\1}",
            $with_vars
        );
        $boxed = preg_replace(
            "/[)][?]/",
            "]",
            preg_replace(
                "/[(][?]:/",
                "[",
                $with_vars_simple
            )
        );
        $unescaped = preg_replace(
            "#[\\\\][.]#",
            ".",
            $boxed
        );
        return $unescaped;
    }

    /**
     * Builds the object
     *
     * @param array $config {
     *  @var callable $errorHandler {
     *      @return callable {
     *          @param \Psr\Http\Message\ServerRequestInterface $request
     *          @param \Psr\Http\Message\ResponseInterface $response
     *          @param \Exception $e
     *          @return \Psr\Http\Message\ResponseInterface|void
     *      }
     *  }
     *  @var callable $notAllowedHandler {
     *      @return callable {
     *          @param \Psr\Http\Message\ServerRequestInterface $request
     *          @param \Psr\Http\Message\ResponseInterface $response
     *          @param array $methods
     *          @return \Psr\Http\Message\ResponseInterface|void
     *      }
     *  }
     *  @var callable $notFoundHandler {
     *      @return callable {
     *          @param \Psr\Http\Message\ServerRequestInterface $request
     *          @param \Psr\Http\Message\ResponseInterface $response
     *          @return \Psr\Http\Message\ResponseInterface|void
     *      }
     *  }
     *  @var callable $phpErrorHandler {
     *      @return callable {
     *          @param \Psr\Http\Message\ServerRequestInterface $request
     *          @param \Psr\Http\Message\ResponseInterface $response
     *          @param \Error $e
     *          @return \Psr\Http\Message\ResponseInterface|void
     *      }
     *  }
     * }
     */
    public function __construct(array $config = []) {
        $this->config = $config;
    }

    /**
     * Adds a handler for any requests on a path
     *
     * @param string $path eg. "/a"
     * @param callable $handler {
     *  @param \Psr\Http\Message\ServerRequestInterface $req
     *  @param \Psr\Http\Message\ResponseInterface $res
     *  @param array $args Fastroute labels to path component strings
     *  @return \Psr\Http\Message\ResponseInterface|void
     * }
     */
    public function any(string $path, callable $handler) {
        $r = self::fastrouteToRegexp($this->pathPrefix . $path);
        $this->handlers[$r]["any"] = $handler;
    }

    /**
     * Adds a handler for DELETE requests on a path
     *
     * @param string $path eg. "/a"
     * @param callable $handler {
     *  @param \Psr\Http\Message\ServerRequestInterface $req
     *  @param \Psr\Http\Message\ResponseInterface $res
     *  @param array $args Fastroute labels to path component strings
     *  @return \Psr\Http\Message\ResponseInterface|void
     * }
     */
    public function delete(string $path, callable $handler) {
        $r = self::fastrouteToRegexp($this->pathPrefix . $path);
        $this->handlers[$r]["delete"] = $handler;
    }

    /**
     * Adds a handler for GET requests on a path
     *
     * @param string $path eg. "/a"
     * @param callable $handler {
     *  @param \Psr\Http\Message\ServerRequestInterface $req
     *  @param \Psr\Http\Message\ResponseInterface $res
     *  @param array $args Fastroute labels to path component strings
     *  @return \Psr\Http\Message\ResponseInterface|void
     * }
     */
    public function get(string $path, callable $handler) {
        $r = self::fastrouteToRegexp($this->pathPrefix . $path);
        $this->handlers[$r]["get"] = $handler;
    }

    /**
     * Supports adding subpaths with a given parent path
     *
     * @param string $path eg. "/a"
     * @param callable $handler {
     *  @param \Celery\App $app
     * }
     */
    public function group(string $path, callable $handler) {
        $new = clone($this);
        // This deliberately leaves $handlers unchanged
        $new->handlers = &$this->handlers;
        $new->pathPrefix .= $path;
        $handler($new);
    }

    /**
     * Handles the supplied request object, including all error handling. You
     * can use this if you have a ServerRequest built elsewhere.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handleRequest(
        \Psr\Http\Message\ServerRequestInterface $request
    ): \Psr\Http\Message\ResponseInterface {
        $response = new \Celery\Response();
        $target_method = strtolower($request->getMethod());
        $target_path = $request->getUri()->getPath();

        $default_error_handler = function($request, $response, $exception) {
            trigger_error($exception);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500)->withHeader("Content-Type", "text/plain");
        };
        $exception_handler = $this->config["errorHandler"] ?
            $this->config["errorHandler"]() :
            $default_error_handler;

        $error_handler = $this->config["phpErrorHandler"] ?
            $this->config["phpErrorHandler"]() :
            $default_error_handler;

        $method_not_allowed_handler = $this->config["notAllowedHandler"] ?
            $this->config["notAllowedHandler"]() :
            function($request, $response, $methods) {
                $response->getBody()->write(
                    "Method not allowed, supported methods are: " .
                    implode(", ", $methods)
                );
                return $response->withStatus(405)->withHeader("Content-Type", "text/plain");
            };
        $not_found_handler = $this->config["notFoundHandler"] ?
            $this->config["notFoundHandler"]() :
            function($request, $response) {
                $response->getBody()->write("Not found");
                return $response->withStatus(404)->withHeader("Content-Type", "text/plain");
            };

        if($request->getMethod() == "HEAD") {
            $matching_methods = [strtolower($request->getMethod()), "get", "any"];
        } else {
            $matching_methods = [strtolower($request->getMethod()), "any"];
        }
        $allowed_methods = [];
        foreach($this->handlers as $path => $method_handler) {
            if(preg_match($path, $target_path, $md)) {
                $allowed_methods = array_merge(
                    $allowed_methods,
                    array_keys($method_handler)
                );
                $methods = array_intersect(
                    $matching_methods,
                    array_keys($method_handler)
                );
                if($methods) {
                    $handler = $method_handler[array_values($methods)[0]];
                    try {
                        $new_response = $handler(
                            $request,
                            $response,
                            array_filter(
                                $md,
                                function($k) {return !is_numeric($k);},
                                ARRAY_FILTER_USE_KEY
                            )
                        );
                    } catch(\Exception $e) {
                        $new_response = $exception_handler($request, $response, $e);
                    } catch(\Error $e) {
                        $new_response = $error_handler($request, $response, $e);
                    }
                    return $new_response ?? $response;
                }
            }
        }
        if($allowed_methods) {
            $literal_methods = array_map(
                "strtoupper",
                $allowed_methods
            );
            if(in_array("GET", $literal_methods) and !in_array("HEAD", $literal_methods)) {
                $literal_methods[] = "HEAD";
            }
            sort($literal_methods);
            $new_response = $method_not_allowed_handler($request, $response, $literal_methods);
        } else {
            $new_response = $not_found_handler($request, $response);
        }
        return $new_response ?? $response;
    }

    /**
     * Adds a handler for HEAD requests on a path
     *
     * @param string $path eg. "/a"
     * @param callable $handler {
     *  @param \Psr\Http\Message\ServerRequestInterface $req
     *  @param \Psr\Http\Message\ResponseInterface $res
     *  @param array $args Fastroute labels to path component strings
     *  @return \Psr\Http\Message\ResponseInterface|void
     * }
     */
    public function head(string $path, callable $handler) {
        $r = self::fastrouteToRegexp($this->pathPrefix . $path);
        $this->handlers[$r]["head"] = $handler;
    }

    /**
     * Adds a handler for specified requests on a path
     *
     * @param string[] $methods eg. ["GET"]
     * @param string $path eg. "/a"
     * @param callable $handler {
     *  @param \Psr\Http\Message\ServerRequestInterface $req
     *  @param \Psr\Http\Message\ResponseInterface $res
     *  @param array $args Fastroute labels to path component strings
     *  @return \Psr\Http\Message\ResponseInterface|void
     * }
     */
    public function map(array $methods, string $path, callable $handler) {
        $r = self::fastrouteToRegexp($this->pathPrefix . $path);
        foreach($methods as $m) {
            $this->handlers[$r][strtolower($m)] = $handler;
        }
    }

    /**
     * Adds a handler for OPTIONS requests on a path
     *
     * @param string $path eg. "/a"
     * @param callable $handler {
     *  @param \Psr\Http\Message\ServerRequestInterface $req
     *  @param \Psr\Http\Message\ResponseInterface $res
     *  @param array $args Fastroute labels to path component strings
     *  @return \Psr\Http\Message\ResponseInterface|void
     * }
     */
    public function options(string $path, callable $handler) {
        $r = self::fastrouteToRegexp($this->pathPrefix . $path);
        $this->handlers[$r]["options"] = $handler;
    }

    /**
     * Adds a handler for POST requests on a path
     *
     * @param string $path eg. "/a"
     * @param callable $handler {
     *  @param \Psr\Http\Message\ServerRequestInterface $req
     *  @param \Psr\Http\Message\ResponseInterface $res
     *  @param array $args Fastroute labels to path component strings
     *  @return \Psr\Http\Message\ResponseInterface|void
     * }
     */
    public function post(string $path, callable $handler) {
        $r = self::fastrouteToRegexp($this->pathPrefix . $path);
        $this->handlers[$r]["post"] = $handler;
    }

    /**
     * Adds a handler for PUT requests on a path
     *
     * @param string $path eg. "/a"
     * @param callable $handler {
     *  @param \Psr\Http\Message\ServerRequestInterface $req
     *  @param \Psr\Http\Message\ResponseInterface $res
     *  @param array $args Fastroute labels to path component strings
     *  @return \Psr\Http\Message\ResponseInterface|void
     * }
     */
    public function put(string $path, callable $handler) {
        $r = self::fastrouteToRegexp($this->pathPrefix . $path);
        $this->handlers[$r]["put"] = $handler;
    }

    /**
     * Handles the request, based on the environment.
     *
     * @param bool $silent Strictly for Slim compat, drops the response send
     * @param array|null $server_params Mostly for testing
     */
    public function run(bool $silent = false, ?array $server_params = null) {
        $request = (new \Celery\ServerRequest())
            ->withServerParams($server_params ?? $_SERVER)
            ->withUploadedFiles($_FILES);
        $response = $this->handleRequest($request, $silent);
        if(!$silent) {
            $this->sendResponse($response);
        }
    }

    /**
     * Sends that response to the client.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function sendResponse(\Psr\Http\Message\ResponseInterface $response) {
        echo $response->getBody();
    }
}
