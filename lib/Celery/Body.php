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
     * @property bool
     */
    private $forRead;

    /**
     * @property iterable|null
     */
    private $iterator;

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
     *
     * @param string|null $filename If provided, this will be a readable stream
     *  attached to that file. Otherwise it will be a read/write stream in
     *  memory.
     */
    public function __construct(?string $filename = null) {
        if($filename !== null) {
            $this->fh = fopen($filename, "r");
            $this->size = fstat($this->fh)["size"];
            $this->forRead = true;
        } else {
            $this->fh = fopen("php://temp", "w+");
            $this->size = null;
            $this->forRead = false;
        }
    }

    /**
     * @inheritdoc
     */
    public function __toString() {
        try {
            if($this->iterator) {
                while($this->iterator->valid()) {
                    $this->iterator->next();
                }
                $this->iterator = null;
            }
        } catch(\Throwable $e) {
            trigger_error($e);
        }
        $actual_size = fstat($this->fh)["size"];
        if($actual_size) {
            if(ftell($this->fh)) {
                rewind($this->fh);
            }
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
        return stream_get_meta_data($this->fh)["seekable"];
    }

    /**
     * @inheritdoc
     */
    public function seek($offset, $whence = SEEK_SET) {
        if($this->iterator) {
            $actual_size = fstat($this->fh)["size"];
            switch($whence) {
                case SEEK_SET:
                    $seek_to = $offset;
                    break;
                case SEEK_CUR:
                    $seek_to = $this->pos + $offset;
                    break;
                case SEEK_END:
                    $seek_to = $actual_size + $offset;
                    break;
            }
            if($seek_to > $actual_size) {
                while($this->iterator->valid()) {
                    if($seek_to <= fstat($this->fh)["size"]) {
                        break;
                    }
                    $this->iterator->next();
                }
            }
        }
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
        $this->pos = ftell($this->fh);
    }

    /**
     * @inheritdoc
     */
    public function isWritable() {
        return !$this->forRead;
    }

    /**
     * @inheritdoc
     */
    public function write($string) {
        if($this->forRead) {
            throw new \RuntimeException("Not writable");
        } else {
            if($this->pos != ftell($this->fh)) {
                fseek($this->fh, $this->pos, SEEK_SET);
            }
            $result = fwrite($this->fh, $string);
            $this->pos = ftell($this->fh);
            return $result;
        }
    }

    /**
     * @inheritdoc
     */
    public function isReadable() {
        return $this->forRead;
    }

    /**
     * @inheritdoc
     */
    public function read($length) {
        if($this->iterator) {
            $actual_size = fstat($this->fh)["size"];
            if($this->pos + 1 >= $actual_size) {
                while($this->iterator->valid()) {
                    if($this->pos + 1 < fstat($this->fh)["size"]) {
                        break;
                    }
                    $this->iterator->next();
                }
            }
        }
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
        if($this->iterator) {
            while($this->iterator->valid()) {
                $this->iterator->next();
            }
            $this->iterator = null;
        }
        $actual_size = fstat($this->fh)["size"];
        if($actual_size) {
            if(ftell($this->fh)) {
                rewind($this->fh);
            }
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
     * Stores an iterator which will update this object with more content when
     * available.
     *
     * The yielded values are not used here - the iterator must update the
     * stream itself, and any yielded value will be ignored.
     *
     * @param iterable|null $iterator
     * @return self
     */
    public function setIterator(?iterable $iterator) {
        $this->iterator = $iterator;
        return $this;
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
