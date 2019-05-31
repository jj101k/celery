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
     * @property int
     */
    private $pos = 0;

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
        $actual_size = fstat($this->fh)["size"];
        if($actual_size) {
            rewind($this->fh);
            return fread($this->fh, $actual_size);
        } else {
            return "";
        }
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
        return $this->pos;
    }

    /**
     * @inheritdoc
     */
    public function eof() {
        if($this->pos != ftell($this->fh)) {
            fseek($this->fh, $this->pos, SEEK_SET);
        }
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
        if($this->pos != ftell($this->fh)) {
            fseek($this->fh, $this->pos, SEEK_SET);
        }
        fseek($this->fh, $offset, $whence);
        $this->pos = ftell($this->fh);
    }

    /**
     * @inheritdoc
     */
    public function rewind() {
        rewind($this->fh);
        $this->pos = 0;
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
        if($this->pos != ftell($this->fh)) {
            fseek($this->fh, $this->pos, SEEK_SET);
        }
        $result = fwrite($this->fh, $string);
        $this->pos = ftell($this->fh);
        return $result;
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
        if($this->pos != ftell($this->fh)) {
            fseek($this->fh, $this->pos, SEEK_SET);
        }
        $result = fread($this->fh, $length);
        $this->pos = ftell($this->fh);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getContents() {
        $actual_size = fstat($this->fh)["size"];
        if($actual_size) {
            rewind($this->fh);
            return fread($this->fh, $actual_size);
        } else {
            return "";
        }
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
