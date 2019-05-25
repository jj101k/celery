<?php
require_once "vendor/autoload.php";
/**
 * Tests request objects
 */
class RequestTest extends \PHPUnit\Framework\TestCase {
    /**
     * Makes sure that file uploads look good
     */
    public function testFileUpload() {
        $a_filename = tempnam(sys_get_temp_dir(), "upload-example");
        $b_filename = tempnam(sys_get_temp_dir(), "upload-example");
        $boundary = rand();
        $a_contents = rand() . rand();
        $b_contents = rand();
        file_put_contents($a_filename, $a_contents);
        file_put_contents($b_filename, $b_contents);
        $r = new \Celery\ServerRequest();
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "QUERY_STRING" => "",
            "REQUEST_URI" => "/",
            "REQUEST_METHOD" => "POST",
            "CONTENT_TYPE" => "multipart/form-data;boundary={$boundary}"
        ])->withUploadedFiles([
            "a" => [
                "name" => "foo.png",
                "type" => "image/png",
                "tmp_name" => $a_filename,
                "error" => 0,
                "size" => strlen($a_contents),
            ],
            "b" => [
                "c" => [
                    "name" => "foo.png",
                    "type" => "image/png",
                    "tmp_name" => $b_filename,
                    "error" => 0,
                    "size" => strlen($b_contents),
                ],
            ],
        ]);
        $this->assertSame(
            strlen($a_contents),
            $r->getUploadedFiles()["a"]->getSize(),
            "getUploadedFiles(): Flat uploaded file is usable"
        );
        $this->assertSame(
            strlen($b_contents),
            $r->getUploadedFiles()["b"]["c"]->getSize(),
            "getUploadedFiles(): Deep uploaded file is usable"
        );
        $this->assertSame(
            $a_contents,
            "" . $r->getUploadedFiles()["a"]->getStream(),
            "getUploadedFiles(): Uploaded file has a stream mode"
        );
        unlink($a_filename);
        unlink($b_filename);
    }
    /**
     * Tests how headers come out
     */
    public function testHeaders() {
        $r = new \Celery\ServerRequest();
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "QUERY_STRING" => "",
            "REQUEST_URI" => "/foo",
            "REQUEST_METHOD" => "POST",
            "CONTENT_TYPE" => "text/plain",
            "HTTP_X_FORWARDED_FOR" => "127.0.0.1, 127.0.0.2"
        ]);
        $this->assertSame(
            "text/plain",
            $r->getHeaderLine("Content-Type"),
            "getHeaderLine(): Returns correct Content-Type:"
        );
        $this->assertSame(
            "127.0.0.1, 127.0.0.2",
            $r->getHeaderLine("X-Forwarded-For"),
            "getHeaderLine(): Returns correct single value for X-Forwarder-For"
        );
        $r = $r->withAddedHeader("x-forwarded-for", "127.0.0.8");
        $this->assertSame(
            "127.0.0.1, 127.0.0.2, 127.0.0.8",
            $r->getHeaderLine("X-Forwarded-For"),
            "getHeaderLine(): Added headers are combined correctly"
        );
    }
    /**
     * Tests how headers come out
     */
    public function testPathInfo() {
        $r = new \Celery\ServerRequest();
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "QUERY_STRING" => "",
            "REQUEST_URI" => "/foo",
            "REQUEST_METHOD" => "POST",
            "CONTENT_TYPE" => "text/plain",
            "HTTP_X_FORWARDED_FOR" => "127.0.0.1, 127.0.0.2"
        ]);
        $this->assertSame(
            $r->getUri()->getPath(),
            "/foo",
            "Path matches in the usual case"
        );
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "PATH_INFO" => "/bar",
            "QUERY_STRING" => "",
            "REQUEST_URI" => "/index.php/bar",
            "REQUEST_METHOD" => "POST",
            "CONTENT_TYPE" => "text/plain",
            "HTTP_X_FORWARDED_FOR" => "127.0.0.1, 127.0.0.2"
        ]);
        $this->assertSame(
            $r->getUri()->getPath(),
            "/bar",
            "Path matches in the PATH_INFO case"
        );
    }
    /**
     * Makes sure that ports work via withServerParams
     */
    public function testPortDetection() {
        $r = new \Celery\ServerRequest();
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org:81",
            "QUERY_STRING" => "",
            "REQUEST_URI" => "/",
            "REQUEST_METHOD" => "GET",
        ]);
        $this->assertSame(
            81,
            $r->getUri()->getPort(),
            "Port is detected when supplied"
        );
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "QUERY_STRING" => "",
            "REQUEST_URI" => "/",
            "REQUEST_METHOD" => "GET",
        ]);
        $this->assertNull(
            $r->getUri()->getPort(),
            "Port is null when now supplied"
        );
    }
    /**
     * Tests retention of query string info
     */
    public function testQueryString() {
        $r = new \Celery\ServerRequest();
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "QUERY_STRING" => "a=b&c=d&e[]=f&e[]=g&h[]=i&j[1]=k&l[m][n]=o",
            "REQUEST_URI" => "/foo?a=b&c=d",
            "REQUEST_METHOD" => "POST",
            "CONTENT_TYPE" => "text/plain",
            "HTTP_X_FORWARDED_FOR" => "127.0.0.1, 127.0.0.2"
        ]);
        $this->assertSame(
            $r->getUri()->getQuery(),
            "a=b&c=d&e[]=f&e[]=g&h[]=i&j[1]=k&l[m][n]=o",
            "Query string retained"
        );
        $this->assertSame(
            $r->getQueryParams(),
            [
                "a" => "b",
                "c" => "d",
                "e" => ["f", "g"],
                "h" => ["i"],
                "j" => [1 => "k"],
                "l" => ["m" => ["n" => "o"]],
            ],
            "Query string decoded correctly"
        );
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "QUERY_STRING" => "a=b&c=d",
            "REQUEST_URI" => "/foo?a=b&c=d",
            "REQUEST_METHOD" => "POST",
            "CONTENT_TYPE" => "text/plain",
            "HTTP_X_FORWARDED_FOR" => "127.0.0.1, 127.0.0.2"
        ]);
        $this->assertSame(
            $r->getUri()->getPath(),
            "/foo",
            "Query string in URI: path extracted correctly"
        );
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "QUERY_STRING" => "a=b&c=d",
            "REQUEST_URI" => "/foo",
            "REQUEST_METHOD" => "POST",
            "CONTENT_TYPE" => "text/plain",
            "HTTP_X_FORWARDED_FOR" => "127.0.0.1, 127.0.0.2"
        ]);
        $this->assertSame(
            $r->getUri()->getPath(),
            "/foo",
            "Query string not in URI: path extracted correctly"
        );
        $this->assertSame(
            $r->getUri()->getQuery(),
            "a=b&c=d",
            "Query string not in URI: query string retained"
        );
    }
    /**
     * Tests how headers come out
     */
    public function testScheme() {
        $r = new \Celery\ServerRequest();
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "QUERY_STRING" => "",
            "REQUEST_URI" => "/foo",
            "REQUEST_METHOD" => "POST",
            "CONTENT_TYPE" => "text/plain",
            "HTTP_X_FORWARDED_FOR" => "127.0.0.1, 127.0.0.2"
        ]);
        $this->assertSame(
            $r->getUri()->getScheme(),
            "http",
            "Scheme matches in the usual case"
        );
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "HTTPS" => "1",
            "QUERY_STRING" => "",
            "REQUEST_URI" => "/foo",
            "REQUEST_METHOD" => "POST",
            "CONTENT_TYPE" => "text/plain",
            "HTTP_X_FORWARDED_FOR" => "127.0.0.1, 127.0.0.2"
        ]);
        $this->assertSame(
            $r->getUri()->getScheme(),
            "https",
            "Scheme matches in https mode"
        );
    }
}