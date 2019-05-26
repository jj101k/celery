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
     * Builds the object.
     *
     * @param string|null $header_block eg. "HTTP/1.1 200 OK\r\nContent-Type:
     *  text/plain\r\n\r\n"
     */
    public function __construct(?string $header_block = null) {
        parent::__construct();
        if($header_block) {
            if(preg_match(
                "#^HTTP/(?<version>\d+[.]\d+) (?<status>\d+) (?<reason>[^\r\n]+)\r\n(?<tail>.*)#s",
                $header_block,
                $md
            )) {
                $this->protocolVersion = $md["version"];
                $this->statusCode = +$md["status"];
                $this->reasonPhrase = $md["reason"];
                $header_block = $md["tail"];
            } else {
                throw new \Exception(
                    "Cannot parse non-HTTP line starting: " . explode("\r\n", $header_block)[0]
                );
            }
            while(preg_match(
                "/^([^:]+): ([^\r\n]*(?:\r\n\h[^\r\n]*)*)\r\n(.*)/s",
                $header_block,
                $md
            )) {
                $name = $md[1];
                $content = $md[2];
                $header_block = $md[3];

                $this->setAddedHeader($name, $content);
            }
            $header_block = preg_replace("/^\r\n$/", "", $header_block);
            if($header_block) {
                trigger_error("Left-over garbage: {$header_block}");
            }
        } else {
            $this->setAddedHeader("Content-Type", "text/html");
        }
    }

    /**
     * @inheritdoc
     */
    public function getReasonPhrase() {
        return $this->reasonPhrase;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode() {
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
}
