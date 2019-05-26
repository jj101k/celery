<?php
namespace Celery;
/**
 * General message support. Only used as a superclass.
 */
abstract class Message implements \Psr\Http\Message\MessageInterface {
    /**
     * @property \Psr\Http\Message\StreamInterface
     */
    protected $body;

    /**
     * @property array Maps canonicalised headers to their original
     *  representation
     */
    protected $headerIdentities = [];

    /**
     * @property array {
     *  @var string[] $(header_name)
     * }
     */
    protected $headers = [];

    /**
     * @property string
     */
    protected $protocolVersion;

    /**
     * Adds a header value in place
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    protected function setAddedHeader(string $name, string $value) {
        $cname = @$this->headerIdentities[strtoupper($name)];
        if($cname) {
            $this->headers[$cname][] = $value;
        } else {
            $this->headerIdentities[strtoupper($name)] = $name;
            $this->headers[$name][] = $value;
        }
        return $this;
    }

    /**
     * Builds the object
     */
    public function __construct() {
        $this->body = new \Celery\Body();
    }

    /**
     * @inheritdoc
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function getHeader($name) {
        $cname = @$this->headerIdentities[strtoupper($name)];
        if($cname) {
            return $this->headers[$cname];
        } else {
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function getHeaderLine($name) {
        return implode(", ", $this->getHeader($name));
    }

    /**
     * @inheritdoc
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function getProtocolVersion() {
        return $this->protocolVersion;
    }

    /**
     * @inheritdoc
     */
    public function hasHeader($name) {
        $cname = @$this->headerIdentities[strtoupper($name)];
        return !!$cname;
    }

    /**
     * @inheritdoc
     */
    public function withAddedHeader($name, $value) {
        $new = clone($this);
        return $new->setAddedHeader($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function withBody(\Psr\Http\Message\StreamInterface $body) {
        $new = clone($this);
        $new->body = $body;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withHeader($name, $value) {
        $new = clone($this);
        $cvalue = is_array($value) ? $value : [$value];
        $cname = @$new->headerIdentities[strtoupper($name)];
        if($cname) {
            $new->headers[$cname] = $cvalue;
        } else {
            $new->headerIdentities[strtoupper($name)] = $name;
            $new->headers[$name] = $cvalue;
        }
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withoutHeader($name) {
        $new = clone($this);
        $cname = @$new->headerIdentities[strtoupper($name)];
        if($cname) {
            unset($new->headers[$cname]);
            unset($new->headerIdentities[strtoupper($name)]);
        }
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withProtocolVersion($version) {
        $new = clone($this);
        $new->protocolVersion = $version;
        return $new;
    }
}
