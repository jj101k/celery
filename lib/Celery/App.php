<?php
namespace Celery;
/**
 * The main entry point for an HTTP service. This should be broadly compatibile
 * with \Slim\App
 */
class App {
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
     * Sends that response to the client.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function sendResponse(\Psr\Http\Message\ResponseInterface $response) {
        echo $response->getBody();
    }
}