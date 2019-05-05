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
     * @inheritdoc
     */
    public function withStatus($code, $reasonPhrase = '') {
        $new = clone($this);
        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }
}
