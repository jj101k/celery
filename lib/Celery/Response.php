<?php
namespace Celery;
/**
 * This is the main response object you use.
 */
class Response extends \Celery\Message implements \Psr\Http\Message\ResponseInterface {
    /**
     * @property string
     */
    private $reasonPhrase = "";

    /**
     * @property int
     */
    private $statusCode;

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
