<?php
namespace Celery;
/**
 * The request object with server stuff added. This is the common request object
 * you'd use.
 */
class ServerRequest extends \Celery\Request implements \Psr\Http\Message\ServerRequestInterface {
    /**
     * @property array
     */
    private $attributes;

    /**
     * @property array
     */
    private $cookieParams;

    /**
     * @property null|array|object
     */
    private $parsedBody;

    /**
     * @property array
     */
    private $queryParams;

    /**
     * @property array
     */
    private $serverParams;

    /**
     * @property array
     */
    private $uploadedFiles;

    /**
     * @inheritdoc
     */
    public function getAttribute($name, $default = null) {
        if(array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        } else {
            return $default;
        }
    }

    /**
     * @inheritdoc
     */
    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * @inheritdoc
     */
    public function getCookieParams() {
        return $this->cookieParams;
    }

    /**
     * @inheritdoc
     */
    public function getParsedBody() {
        return $this->parsedBody;
    }

    /**
     * @inheritdoc
     */
    public function getQueryParams() {
        return $this->queryParams;
    }

    /**
     * @inheritdoc
     */
    public function getServerParams() {
        return $this->serverParams;
    }

    /**
     * @inheritdoc
     */
    public function getUploadedFiles() {
        return $this->uploadedFiles;
    }

    /**
     * @inheritdoc
     */
    public function withAttribute($name, $value) {
        $new = clone($this);
        $new->attributes[$name] = $value;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withCookieParams(array $cookies) {
        $new = clone($this);
        $new->cookieParams = $cookies;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withoutAttribute($name) {
        $new = clone($this);
        unset($new->attributes[$name]);
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withParsedBody($data) {
        $new = clone($this);
        $new->parsedBody = $data;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withQueryParams(array $query) {
        $new = clone($this);
        $new->queryParams = $query;
        return $new;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles) {
        $new = clone($this);
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }
}
