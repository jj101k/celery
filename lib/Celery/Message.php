<?php
namespace Celery;
/**
 * General message support. Only used as a superclass.
 */
abstract class Message implements \Psr\Http\Message\MessageInterface {
    /**
     * @property \Psr\Http\Message\StreamInterface
     */
    private $body;

    /**
     * @property array {
     *  @var string[] $(header_name)
     * }
     */
    private $headers;

    /**
     * @property string
     */
    private $protocolVersion;

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
        $m = "/^" . preg_replace("#/#", "\\x2f", quotemeta($name)) . "$/i";
        foreach($this->headers as $header_name => $values) {
            if(preg_match($m, $header_name)) {
                return $values;
            }
        }
        return [];
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
        $m = "/^" . preg_replace("#/#", "\\x2f", quotemeta($name)) . "$/i";
        foreach(array_keys($this->headers) as $header_name) {
            if(preg_match($m, $header_name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function withAddedHeader($name, $value) {
        $new = clone($this);
        if(is_array($value)) {
            $new->headers[$name] = array_merge( // <-- NAME
                $this->getHeaders($name),
                $value
            );
        } else {
            $new->headers[$name] = array_merge(
                $this->getHeaders($name),
                [$value]
            );
        }
        return $new;
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
        if(is_array($value)) {
            $new->headers[$name] = $value;
        } else {
            $new->headers[$name] = [$value];
        }
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withoutHeader($name) {
        $new = clone($this);
        $m = "/^" . preg_replace("#/#", "\\x2f", quotemeta($name)) . "$/i";
        foreach(array_keys($this->headers) as $header_name) {
            if(preg_match($m, $header_name)) {
                unset($this->headers[$name]);
                return $new;
            }
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
