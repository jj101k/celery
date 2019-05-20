<?php
namespace Celery;
/**
 * This is the main response object you use.
 */
class Response extends \Celery\Message implements \Psr\Http\Message\ResponseInterface {
    /**
     * @property iterable<string>|null
     */
    private $iterator = null;

    /**
     * @property string
     */
    private $reasonPhrase = "";

    /**
     * @property int
     */
    private $statusCode;

    /**
     * Fetches the HTTP headers from the iterator. This will rewrite relevant
     * parts of the object.
     */
    private function getFirstLine() {
        // Remove it
        $iterator = $this->iterator;
        $this->iterator = null;

        // Get the first item and step forward
        $headers = $iterator->current();
        $iterator->next();

        if(preg_match(
            "#^HTTP/(?<version>\d+[.]\d+) (?<status>\d+) (?<reason>[^\r\n]+)\r\n(?<tail>.*)#s",
            $headers,
            $md
        )) {
            $this->protocolVersion = $md["version"];
            $this->statusCode = +$md["status"];
            $this->reasonPhrase = $md["reason"];
            $headers = $md["tail"];
        } else {
            throw new \Exception(
                "Cannot parse non-HTTP line starting: " . explode("\r\n", $headers)[0]
            );
        }
        while(preg_match(
            "/^([^:]+): ([^\r\n]*(?:\r\n\h[^\r\n]*)*)\r\n(.*)/s",
            $headers,
            $md
        )) {
            $name = $md[1];
            $content = $md[2];
            $headers = $md[3];

            $this->setAddedHeader($name, $content);
        }
        $headers = preg_replace("/^\r\n$/", "", $headers);
        if($headers) {
            trigger_error("Left-over garbage: {$headers}");
        }
        $this->body = new \Celery\IterableBody($iterator);
    }

    /**
     * Builds the object. If you supply an iterator, the first response must be
     * the HTTP line and all headers; the second and later responses can be
     * parts of the body.
     *
     * @param iterable<string>|null $iterator
     */
    public function __construct(?iterable $iterator = null) {
        parent::__construct();
        $this->iterator = $iterator;
        if(!$iterator) {
            $this->setAddedHeader("Content-Type", "text/html");
        }
    }

    /**
     * @inheritdoc
     */
    public function getReasonPhrase() {
        if($this->iterator) {
            $this->getFirstLine();
        }
        return $this->reasonPhrase;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode() {
        if($this->iterator) {
            $this->getFirstLine();
        }
        return $this->statusCode;
    }

    /**
     * This is for Slim compat.
     *
     * @param mixed $content
     * @param int $http_code Optional, defaults to 200
     * @param int $encode_options Optional, defaults to 0
     * @return static
     */
    public function withJSON(
        $content,
        int $http_code = 200,
        int $encode_options = 0
    ) {
        $encoded_content = json_encode($content, $encode_options);

        $body = new \Celery\Body();
        $body->write($encoded_content);
        return $this->withBody($body)->withStatus($http_code);
    }

    /**
     * @inheritdoc
     */
    public function withStatus($code, $reasonPhrase = '') {
        $new = clone($this);
        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function getBody() {
        if($this->iterator) {
            $this->getFirstLine();
        }
        return parent::getBody();
    }

    /**
     * @inheritdoc
     */
    public function withHeader($name, $value) {
        if($this->iterator) {
            $this->getFirstLine();
        }
        return parent::withHeader($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function withoutHeader($name) {
        if($this->iterator) {
            $this->getFirstLine();
        }
        return parent::withoutHeader($name);
    }

    /**
     * @inheritdoc
     */
    public function getHeader($name) {
        if($this->iterator) {
            $this->getFirstLine();
        }
        return parent::getHeader($name);
    }

    /**
     * @inheritdoc
     */
    public function getHeaders() {
        if($this->iterator) {
            $this->getFirstLine();
        }
        return parent::getHeaders();
    }

    /**
     * @inheritdoc
     */
    public function getProtocolVersion() {
        if($this->iterator) {
            $this->getFirstLine();
        }
        return parent::getProtocolVersion();
    }

    /**
     * @inheritdoc
     */
    public function hasHeader($name) {
        if($this->iterator) {
            $this->getFirstLine();
        }
        return parent::hasHeader($name);
    }
}
