<?php
namespace Celery;
/**
 * This is a trivial PSR stream wrapping a file
 */
class StreamFile implements \Psr\Http\Message\StreamInterface {
    /**
     * @property resource|null
     */
    private $filehandle;

    /**
     * @param string $filename
     */
    public function __construct(string $filename) {
        $this->filehandle = fopen($filename, "r");
    }

    /**
     * @inheritdoc
     */
    public function __toString() {
        try {
            $this->rewind();
            return $this->getContents();
        } catch(\Throwable $e) {
            trigger_error($e);
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function close() {
        fclose($this->filehandle);
    }

    /**
     * @inheritdoc
     */
    public function detach() {
        $f = $this->filehandle;
        $this->filehandle = null;
        return $f;
    }

    /**
     * @inheritdoc
     */
    public function getSize() {
        return fstat($this->filehandle)["size"];
    }

    /**
     * @inheritdoc
     */
    public function tell() {
        return ftell($this->filehandle);
    }

    /**
     * @inheritdoc
     */
    public function eof() {
        return feof($this->filehandle);
    }

    /**
     * @inheritdoc
     */
    public function isSeekable() {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function seek($offset, $whence = SEEK_SET) {
        return fseek($this->filehandle, $offset, $whence);
    }

    /**
     * @inheritdoc
     */
    public function rewind() {
        return rewind($this->filehandle);
    }

    /**
     * @inheritdoc
     */
    public function isWritable() {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function write($string) {
        throw new \RuntimeException("Not writable");
    }

    /**
     * @inheritdoc
     */
    public function isReadable() {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function read($length) {
        return fread($this->filehandle, $length);
    }

    /**
     * @inheritdoc
     */
    public function getContents() {
        return stream_get_contents($this->filehandle);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($key = null) {
        return stream_get_meta_data($this->filehandle);
    }
}
