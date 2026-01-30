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
            ->onlyMethods(["sendHeaders"])
            ->getMock();

        $app->method("sendHeaders")->willReturn(null);
        $app->get("/", function(
            ServerRequestInterface $req,
            ResponseInterface $res,
            array $args
        ) {
            $body = new \Celery\Body();
            $bx = clone($body);
            $body->setIterator(
                (function($b) {
                    $b->write("Hello");
                    yield;
                    usleep(10000);
                    $b->write("There");
                    yield;
                    usleep(10000);
                    $b->write("World");
                })($bx)
            );
            return $res->withBody($body);
        });

        $written = [];
        $times = [];

        ob_start(function(string $buffer, int $phase) use (&$written, &$times) {
            $written[] = $buffer;
            $times[] = microtime(true);
        }, 1);
        $app->run(false, [
            "REQUEST_METHOD" => "get",
            "REQUEST_URI" => "/",
            "QUERY_STRING" => "",
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
