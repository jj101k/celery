<?php
namespace Celery;
/**
 * General message support. This may be used as a client request (to set up a
 * call to another service), but for server requests you should use
 * \Celery\ServerRequest instead.
 */
class Request extends \Celery\Message implements \Psr\Http\Message\RequestInterface {
    /**
     * @property string
     */
    private $httpMethod;

    /**
     * @property string
     */
    private $requestTarget;

    /**
     * @property \Psr\Http\Message\UriInterface
     */
    private $uri;

    /**
     * Builds the object
     */
    public function __construct() {
        $this->uri = new \Celery\Uri();
    }

    /**
     * @inheritdoc
     */
    public function getMethod() {
        return $this->httpMethod;
    }

    /**
     * @inheritdoc
     */
    public function getRequestTarget() {

    }

    /**
     * @inheritdoc
     */
    public function getUri() {
        return $this->uri;
    }

    /**
     * @inheritdoc
     */
    public function withMethod($method) {
        $new = clone($this);
        $new->httpMethod = $method;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withRequestTarget($requestTarget) {
        $new = clone($this);
        $new->requestTarget = $requestTarget;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withUri(\Psr\Http\Message\UriInterface $uri, $preserveHost = false) {
        $new = clone($this);
        if($preserveHost or !$uri->getHost()) {
            $new->uri = $uri->withHost($this->uri->getHost());
        } else {
            $new->uri = $uri;
        }
        return $new;
    }
}
