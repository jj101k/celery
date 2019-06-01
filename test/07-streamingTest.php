<?php
require_once "vendor/autoload.php";
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
/**
 * Tests streaming-related functionality (top level)
 */
class StreamingTest extends \PHPUnit\Framework\TestCase {
    /**
     * Makes sure that responses can be streamed
     */
    public function testStreaming() {
        $app = $this
            ->getMockBuilder("\Celery\App")
            ->setMethods(["sendHeaders"])
            ->getMock();

        $app->method("sendHeaders")->willReturn(null);
        $app->get("/", function(
            ServerRequestInterface $req,
            ResponseInterface $res,
            array $args
        ) {
            $body = new \Celery\Body();
            $b = clone($body);
            $body->setIterator(
                (function($b) {
                    $b->write("Hello");
                    yield;
                    usleep(10000);
                    $b->write("There");
                    yield;
                    usleep(10000);
                    $b->write("World");
                })($b)
            );
            return $res->withBody($body);
        });

        $written = [];
        $times = [];
        ob_start(function($buffer) use (&$written, &$times) {
            $written[] = $buffer;
            $times[] = microtime(true);
            return "";
        }, 1);
        $app->run(false, [
            "REQUEST_METHOD" => "get",
            "REQUEST_URI" => "/",
        ]);
        ob_end_flush();
        $this->assertSame(
            ["Hello", "There", "World"],
            array_slice($written, 0, 3),
            "Streaming: Can write body in multiple chunks"
        );
        $this->assertGreaterThan(
            $times[0] + 0.009,
            $times[1],
            "Streaming: blocks were not sent at the same time"
        );
    }
}
