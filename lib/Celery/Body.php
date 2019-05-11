<?php
namespace Celery;
/**
 * This is a trivial PSR message body. It does nothing fancy.
 */
class Body implements \Psr\Http\Message\StreamInterface {
    /**
     * @property string
     */
    private $content = "";

    /**
     * @inheritdoc
     */
    public function __toString() {
        return $this->content;
    }

    /**
     * @inheritdoc
     */
    public function close() {
        //
    }

    /**
     * @inheritdoc
     */
    public function detach() {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSize() {
        return strlen($this->content);
    }

    /**
     * @inheritdoc
     */
    public function tell() {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function eof() {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isSeekable() {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function seek($offset, $whence = SEEK_SET) {
        throw new \RuntimeException("Not seekable");
    }

    /**
     * @inheritdoc
     */
    public function rewind() {
        throw new \RuntimeException("Not seekable");
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
        $this->content .= $string;
        return strlen($string);
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
        return substr($this->content, 0, $length);
    }

    /**
     * @inheritdoc
     */
    public function getContents() {
        return $this->content;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($key = null) {
        return null;
    }
}
