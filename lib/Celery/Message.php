<?php
namespace Celery;
/**
 * General message support. Only used as a superclass.
 */
abstract class Message implements \Psr\Http\Message\MessageInterface {
    /**
     * @property callable|null
     */
    protected $beforeFirstUse;

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
     * Returns a clone, triggering beforeFirstUse if it exists
     *
     * @return static
     */
    protected function clone() {
        if($this->beforeFirstUse) {
            $this->firstUse();
        }
        return clone($this);
    }

    /**
     * Runs the beforeFirstUse handler, and removes it.
     *
     * @return void
     */
    protected function firstUse(): void {
        $handler = $this->beforeFirstUse;
        $this->beforeFirstUse = null;
        call_user_func($handler);
    }

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
     *
     * @param callable|null $before_first_use If set, this will be called before
     *  the first property is fetched or before the object is cloned.
     */
    public function __construct(?callable $before_first_use) {
        $this->beforeFirstUse = $before_first_use;
        $this->body = new \Celery\Body();
    }

    /**
     * @inheritdoc
     */
    public function getBody() {
        if($this->beforeFirstUse) {
            $this->firstUse();
        }
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function getHeader($name) {
        if($this->beforeFirstUse) {
            $this->firstUse();
        }
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
        if($this->beforeFirstUse) {
            $this->firstUse();
        }
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function getProtocolVersion() {
        if($this->beforeFirstUse) {
            $this->firstUse();
        }
        return $this->protocolVersion;
    }

    /**
     * @inheritdoc
     */
    public function hasHeader($name) {
        if($this->beforeFirstUse) {
            $this->firstUse();
        }
        $cname = @$this->headerIdentities[strtoupper($name)];
        return !!$cname;
    }

    /**
     * @inheritdoc
     */
    public function withAddedHeader($name, $value) {
        $new = $this->clone();
        return $new->setAddedHeader($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function withBody(\Psr\Http\Message\StreamInterface $body) {
        $new = $this->clone();
        $new->body = $body;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withHeader($name, $value) {
        $new = $this->clone();
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
        $new = $this->clone();
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
        $new = $this->clone();
        $new->protocolVersion = $version;
        return $new;
    }
}
