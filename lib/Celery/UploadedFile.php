<?php
namespace Celery;
/**
 * Simple wrapper around $_FILES
 */
class UploadedFile implements \Psr\Http\Message\UploadedFileInterface {
    /**
     * @property \Psr\Http\Message\StreamInterface|nul;
     */
    private $contentStream;

    /**
     * @property int
     */
    private $errorCode;

    /**
     * @property string|null
     */
    private $mimeType;

    /**
     * @property string|null
     */
    private $name;

    /**
     * @property int
     */
    private $size;

    /**
     * @property string|null
     */
    private $viaFilename;

    /**
     * Builds the object.
     *
     * @param array $f As a member of $_FILES
     */
    public function __construct(array $f) {
        $this->errorCode = $f["error"];
        $this->mimeType = $f["type"];
        $this->name = $f["name"];
        $this->size = $f["size"];
        $this->viaFilename = $f["tmp_name"];
    }

    /**
     * @inheritdoc
     */
    public function getStream() {
        if($this->viaFilename) {
            if(!$this->contentStream) {
                $this->contentStream = new \Celery\Body($this->viaFilename);
            }
            return $this->contentStream;
        } else {
            throw new \RuntimeException("The file is no longer there");
        }
    }

    /**
     * @inheritdoc
     */
    public function moveTo($targetPath) {
        if(!$this->viaFilename) {
            throw new \RuntimeException("File has already been moved");
        }
        if(is_uploaded_file($this->viaFilename)) {
            move_uploaded_file($this->viaFilename, $targetPath);
        } else {
            rename($this->viaFilename, $targetPath);
        }
        $this->viaFilename = null;
        if($this->contentStream) {
            $this->contentStream->close();
            $this->contentStream = null;
        }
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
    public function getError() {
        return $this->errorCode;
    }

    /**
     * @inheritdoc
     */
    public function getClientFilename() {
        return $this->name;
    }

    /**
     * @inheritdocs
     */
    public function getClientMediaType() {
        return $this->mimeType;
    }
}
