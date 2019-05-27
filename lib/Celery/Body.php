<?php
namespace Celery;
/**
 * This is a trivial PSR message body. It does nothing fancy.
 */
class Body implements \Psr\Http\Message\StreamInterface {
    /**
     * @property resource|null
     */
    private $fh;

    /**
     * @property int|null
     */
    private $size = null;

    /**
     * Builds the object.
     */
    public function __construct() {
        $this->fh = fopen("php://memory", "w+");
    }

    /**
     * @inheritdoc
     */
    public function __toString() {
        rewind($this->fh);
        return fread($this->fh, fstat($this->fh)["size"]);
    }

    /**
     * @inheritdoc
     */
    public function close() {
        fclose($this->fh);
    }

    /**
     * @inheritdoc
     */
    public function detach() {
        $fh = $this->fh;
        $this->fh = null;
        return $fh;
    }

    /**
     * @inheritdoc
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @inheritdoc
     */
    public function tell() {
        return ftell($this->fh);
    }

    /**
     * @inheritdoc
     */
    public function eof() {
        return feof($this->fh);
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
        fseek($this->fh, $offset, $whence);
    }

    /**
     * @inheritdoc
     */
    public function rewind() {
        rewind($this->fh);
    }

    /**
     * @inheritdoc
     */
    public function isWritable() {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function write($string) {
        return fwrite($this->fh, $string);
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
        return fread($this->fh, $length);
    }

    /**
     * @inheritdoc
     */
    public function getContents() {
        rewind($this->fh);
        return fread($this->fh, fstat($this->fh)["size"]);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($key = null) {
        $data = stream_get_meta_data($this->fh);
        if($key !== null) {
            return $data[$key];
        } else {
            return $data;
        }
    }

    /**
     * @param int $size
     * @return self
     */
    public function setSize(int $size) {
        $this->size = $size;
        return $this;
    }
}
