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

    public function testHead() {
        $r = new \Celery\Response("HTTP/1.1 200 OK\r\nHost: localhost\r\nContent-Type: application/json\r\nContent-Length: 500\r\n\r\n");
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
            500,
            +$r->getHeaderLine("Content-Length"),
            "Expected Content-Length: header"
        );
        $this->assertSame(
            "",
            $r->getBody()->getContents(),
            "Expected content (getContents)"
        );
        $this->assertSame(
            "",
            "" . $r->getBody(),
            "Expected content (__toString)"
        );
    }

    public function testStreaming() {
        $r = new \Celery\Response("HTTP/1.1 200 OK\r\nHost: localhost\r\nContent-Type: application/json\r\n\r\n");
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
        $r->getBody()->write("{\"foo\":");
        $r->getBody()->write("true}");
        $this->assertSame(
            "{\"foo\":true}",
            $r->getBody()->getContents(),
            "Expected content"
        );
    }

    public function testStreamingWithIterator() {
        $body = new \Celery\Body();
        $b = clone($body);
        $body->setIterator(
            (function($b) {
                yield;
                $b->write("seek");
                yield;
                yield;
                $b->write("read");
                yield;
                yield;
                $b->write("everything else");
            })($b)
        );
        $body->seek(1); // Should advance
        $this->assertSame(
            "eek",
            $body->read(3),
            "seek() -> read() works"
        );
        $this->assertSame(
            "read",
            $body->read(4),
            "read() when empty works"
        );
        // getContents (A)
        $this->assertSame(
            "seekreadeverything else",
            $body->getContents(),
            "getContents() reads the rest"
        );
        $body = new \Celery\Body();
        $b = clone($body);
        $body->setIterator(
            (function($b) {
                yield;
                $b->write("except");
                yield;
                throw new \Exception("Test");
            })($b)
        );
        @$this->assertSame(
            "except",
            "" . $body,
            "__toString() reads the rest up to a crash"
        );
    }
}