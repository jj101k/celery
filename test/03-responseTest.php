<?php
require_once "vendor/autoload.php";
/**
 * Tests response objects
 */
class ResponseTest extends \PHPUnit\Framework\TestCase {
    /**
     * Makes sure the body is writable in all the expected senses
     */
    public function testBody() {
        $app = new \Celery\App();
        $response = new \Celery\Response();

        $content = "" . rand();
        $response->getBody()->write($content);

        $this->assertSame(
            $content,
            "" . $response->getBody(),
            "Body write works"
        );
        $response = $response->withJSON([
            "error" => [
                "code" => 0,
                "message" => "Unknown error",
            ],
        ]);
        $this->assertRegExp(
            "/^[{]/",
            "" . $response->getBody(),
            "Writing JSON after other content works"
        );
    }

    public function testStreaming() {
        $fake_http_response = (function() {
            yield "HTTP/1.1 200 OK\r\nHost: localhost\r\nContent-Type: application/json\r\n";
            yield "{\"foo\":";
            yield "true}";
        })();
        $r = new \Celery\Response($fake_http_response);
        $this->assertSame(
            200,
            $r->getStatusCode(),
            "Expected response code"
        );
        $this->assertSame(
            "OK",
            $r->getReasonPhrase(),
            "Expected response text"
        );
        $this->assertSame(
            "application/json",
            $r->getHeaderLine("Content-Type"),
            "Expected Content-Type: header"
        );
        $this->assertSame(
            "{\"foo\":true}",
            $r->getBody()->getContents(),
            "Expected content"
        );
    }
}