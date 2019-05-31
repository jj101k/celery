<?php
require_once "vendor/autoload.php";
/**
 * Tests body objects
 */
class BodyTest extends \PHPUnit\Framework\TestCase {
    /**
     * Make sure that using an iterator to import more data works
     */
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