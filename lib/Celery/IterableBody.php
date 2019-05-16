<?php
namespace Celery;
/**
 * A body from an iterator
 */
class IterableBody implements \Psr\Http\Message\StreamInterface {
    /**
     * @var int The number of bytes at which to drop the read cache
     */
    private static $READ_CACHE_THRESHOLD = 4096;

    /**
     * @property iteratable
     */
    private $iterator;

    /**
     * @property int
     */
    private $pos = 0;

    /**
     * @property string The content from the iterator that has not yet been read
     */
    private $readBuffer = "";

    /**
     * @property string|null While viable, this will retain the read content for
     * replaying.
     */
    private $readCache = "";

    /**
     * Builds the object
     *
     * @param iterable $iterator
     */
    public function __construct(iterable $iterator) {
        $this->iterator = $iterator;
    }

    /**
     * @inheritdoc
     */
    public function __toString() {
        try {
            return $this->getContents();
        } catch(\Throwable $e) {
            trigger_error($e);
            return "";
        }
    }

    /**
     * @inheritdoc
     */
    public function close() {
        // Can't actually do that.
    }

    /**
     * @inheritdoc
     */
    public function detach() {
        return null; // No actual stream
    }

    /**
     * @inheritdoc
     */
    public function getSize() {
        return null; // No way to tell
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
        return($this->readBuffer == "" and !$this->iterator->valid());
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
        $out = $this->readBuffer;
        if(strlen($out) >= $length) {
            $this->pos += $length;
            $this->readBuffer = substr($this->readBuffer, $length);
            return substr($out, 0, $length);
        } elseif(strlen($out)) {
            $this->pos += strlen($out);
            $this->readBuffer = "";
            return $out;
        } else {
            $v = next($this->iterator);
            if($v === false) {
                return "";
            } else {
                if($this->readBuffer !== null) {
                    if(strlen($this->readBuffer) + strlen($v) >= self::$READ_CACHE_THRESHOLD) {
                        $this->readBuffer = null;
                    } else {
                        $this->readBuffer .= $v;
                    }
                }
                if(strlen($v) > $length) {
                    $this->readBuffer = substr($v, $length);
                    return substr($v, 0, $length);
                } else {
                    $this->pos += strlen($v);
                    return $v;
                }
            }
        }
    }

    /**
     * @inheritdoc
     *
     * If the read cache is already null, this won't return anything but will
     * exhaust the iterator.
     */
    public function getContents() {
        if(!$this->eof()) {
            if($this->readCache === null) {
                while($this->iterator->valid()) {
                    $this->iterator->next();
                }
            } else {
                $out = $this->readCache;
                while($this->iterator->valid()) {
                    $out .= $this->iterator->current();
                    $this->iterator->next();
                }
                if(strlen($out) < self::$READ_CACHE_THRESHOLD) {
                    $this->readCache = $out;
                }
                return $out;
            }
        }
        return $this->readCache;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($key = null) {
        return [];
    }
}