<?php
namespace Celery;
/**
 * The request object with server stuff added. This is the common request object
 * you'd use.
 */
class ServerRequest extends \Celery\Request implements \Psr\Http\Message\ServerRequestInterface {
    /**
     * @param array $uploadedFiles as $_FILES
     * @return array Values are either recursive arrays or \Psr\Http\Message\UploadedFileInterface
     */
    private static function uploadedFilesTree(array $uploadedFiles): array {
        return array_map(
            function($f) {
                if(@$f["tmp_name"]) {
                    return new \Celery\UploadedFile($f);
                } else {
                    return self::uploadedFilesTree($f);
                }
            },
            $uploadedFiles
        );
    }

    /**
     * @property array
     */
    private $attributes;

    /**
     * @property array
     */
    private $cookieParams;

    /**
     * @property bool
     */
    private $hasParsed = false;

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
        if(!$this->hasParsed) {
            $content_type = $this->getHeaderLine("Content-Type");
            if(preg_match("#^application/json#", $content_type)) {
                $this->parsedBody = json_decode("" . $this->getBody());
            } elseif(preg_match("#^application/x-www-form-urlencoded#", $content_type)) {
                parse_str("" . $this->getBody(), $this->parsedBody);
            }
            $this->hasParsed = true;
        }
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
        $new->hasParsed = true;
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
     * @inheritdoc
     */
    public function withUploadedFiles(array $uploadedFiles) {
        $new = clone($this);
        $new->uploadedFiles = self::uploadedFilesTree($uploadedFiles);
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withUri(\Psr\Http\Message\UriInterface $uri, $preserveHost = false) {
        parse_str($uri->getQuery(), $params);
        return parent::withUri($uri, $preserveHost)->withQueryParams(
            $params
        );
    }

    /**
     * Imports the given server params (or, if null, those from $_SERVER).
     *
     * @param array|null $params Same format as $_SERVER
     * @return static
     */
    public function withServerParams(?array $params = null) {
        $params = $params ?? $_SERVER;
        $new = clone($this);
        $new->serverParams = $params;
        @list($host, $port) = explode(":", $params["HTTP_HOST"]);
        $uri = (new \Celery\Uri())
            ->withFullURL($params["REQUEST_URI"])
            ->withScheme(@$params["HTTPS"] ? "https" : "http")
            ->withHost($host)
            ->withPort($port)
            ->withQuery(@$params["QUERY_STRING"]);
        if(@$params["PATH_INFO"]) {
            $uri = $uri->withPath($params["PATH_INFO"]);
        }
        if(@$params["CONTENT_TYPE"]) {
            $new = $new->withHeader("Content-Type", $params["CONTENT_TYPE"]);
        }
        foreach($params as $k => $v) {
            if(preg_match("/^HTTP_(\w+)$/", $k, $md)) {
                $normalised_header = implode(
                    "-",
                    array_map(
                        function($w) {
                            return ucfirst(strtolower($w));
                        },
                        explode("_", $md[1])
                    )
                );
                $new = $new->withHeader($normalised_header, $v);
            }
        }
        return $new->withMethod($params["REQUEST_METHOD"])->withUri($uri);
    }
}
