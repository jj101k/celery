<?php
require_once "vendor/autoload.php";
/**
 * Tests that the URI building works
 */
class UriTest extends \PHPUnit\Framework\TestCase {
    /**
     * Tests that the build-from-string and build-from-parts routines are equivalent
     */
    public function testBuild() {
        $uris = [
            "https://foo:bar@baz.boz:808/birz?too=far&to=go#yes",
            "/foo?bar=baz",
            "/bar/boz",
            "mailto:example@example.org",
            "ftp://foo@baz.boz/mail",
            "http://example.org",
        ];
        foreach($uris as $uri) {
            $parts = parse_url($uri) + [
                "fragment" => null,
                "host" => "",
                "pass" => null,
                "port" => null,
                "query" => null,
                "scheme" => null,
                "user" => "",
            ];
            $u1 = (new \Celery\Uri())
                ->withScheme($parts["scheme"])
                ->withUserInfo($parts["user"], $parts["pass"])
                ->withHost($parts["host"])
                ->withPort($parts["port"])
                ->withPath($parts["path"])
                ->withQuery($parts["query"])
                ->withFragment($parts["fragment"]);
            $this->assertSame(
                $uri,
                "" . $u1,
                "URI built from components works for {$uri}"
            );
            $u2 = (new \Celery\Uri())
                ->withFullURL($uri);
            ksort($parts);
            $this->assertSame(
                $parts,
                [
                    "fragment" => $u2->getFragment(),
                    "host" => $u2->getHost(),
                    "pass" => @explode(":", $u2->getUserInfo())[1],
                    "path" => $u2->getPath(),
                    "port" => $u2->getPort(),
                    "query" => $u2->getQuery(),
                    "scheme" => $u2->getScheme(),
                    "user" => explode(":", $u2->getUserInfo())[0],
                ],
                "URI built from string can be componentised for {$uri}"
            );
        }
    }
}
