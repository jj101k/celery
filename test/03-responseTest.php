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
}