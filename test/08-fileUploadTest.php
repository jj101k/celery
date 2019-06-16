<?php
require_once "vendor/autoload.php";
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
/**
 * Tests file upload-related functionality (top level)
 */
class FileUploadTest extends \PHPUnit\Framework\TestCase {
    /**
     * Makes sure that file uploads look good
     */
    public function testFileUpload() {
        $a_filename = tempnam(sys_get_temp_dir(), "upload-example");
        $b_filename = tempnam(sys_get_temp_dir(), "upload-example");
        $d_filename = tempnam(sys_get_temp_dir(), "upload-example");
        $boundary = rand();
        $a_contents = rand() . rand();
        $b_contents = rand();
        $d_contents = str_repeat(rand(), rand(0, 65535));
        file_put_contents($a_filename, $a_contents);
        file_put_contents($b_filename, $b_contents);
        file_put_contents($d_filename, $d_contents);
        $r = new \Celery\ServerRequest();
        $r = $r->withServerParams([
            "HTTP_HOST" => "example.org",
            "QUERY_STRING" => "",
            "REQUEST_URI" => "/",
            "REQUEST_METHOD" => "POST",
            "CONTENT_TYPE" => "multipart/form-data;boundary={$boundary}"
        ])->withUploadedFiles(
            array_map(
                ["\Celery\ServerRequest", "uploadedFilesTree"],
                [
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
                    "d" => [
                        "name" => "foo.png",
                        "type" => "image/png",
                        "tmp_name" => $d_filename,
                        "error" => 0,
                        "size" => strlen($d_contents),
                    ],
                ]
            )
        );
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
        $d_out = "";
        $f = $r->getUploadedFiles()["d"]->getStream();
        while(!$f->eof()) {
            $d_out .= $f->read(256);
        }
        $this->assertSame(
            $d_contents,
            $d_out,
            "getUploadedFiles(): Uploaded file works for streamed read"
        );
        unlink($a_filename);
        unlink($b_filename);
        unlink($d_filename);
    }
}
