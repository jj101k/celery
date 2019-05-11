<?php
namespace Celery;
/**
 * Basic URI support. You can safely use an alternative to this.
 */
class Uri implements \Psr\Http\Message\UriInterface {
    /**
     * @property string
     */
    private $fragment = "";

    /**
     * @property string
     */
    private $host = "";

    /**
     * @property string
     */
    private $path;

    /**
     * @property int|null
     */
    private $port;

    /**
     * @property string
     */
    private $query;

    /**
     * @property string
     */
    private $scheme;

    /**
     * @inheritdoc
     */
    public function getAuthority() {
        if($this->host) {
            if($this->port) {
                $location = "{$this->host}:{$this->port}";
            } else {
                $location = $this->host;
            }
            if($this->userInfo) {
                return "{$this->userInfo}@{$location}";
            } else {
                return $location;
            }
        } else {
            return "";
        }
    }

    /**
     * @inheritdoc
     */
    public function getFragment() {
        return $this->fragment;
    }

    /**
     * @inheritdoc
     */
    public function getHost() {
        return strtolower($this->host);
    }

    /**
     * @inheritdoc
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @inheritdoc
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @inheritdoc
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * @inheritdoc
     */
    public function getScheme() {
        return $this->scheme;
    }

    /**
     * @inheritdoc
     */
    public function getUserInfo() {
        return $this->userInfo;
    }

    /**
     * @inheritdoc
     */
    public function __toString() {
        $out = "";
        if($this->scheme) {
            $out .= "{$this->scheme}:";
        }
        $authority = $this->getAuthority();
        if($authority) {
            $out .= "//{$authority}";
            if(preg_match("#^/#", $this->path)) {
                $out .= $this->path;
            } else {
                $out .= "/{$this->path}";
            }
        } else {
            $out .= preg_replace("#^//+#", "/", $this->path);
        }
        if($this->query) {
            $out .= "?{$this->query}";
        }
        if($this->fragment) {
            $out .= "#{$this->fragment}";
        }
        return $out;
    }

    /**
     * @inheritdoc
     */
    public function withFragment($fragment) {
        $new = clone($this);
        $new->fragment = $fragment;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withHost($host) {
        $new = clone($this);
        $new->host = $host;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withPath($path) {
        $new = clone($this);
        $new->path = $path;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withPort($port) {
        $new = clone($this);
        $new->port = isset($port) ? +$port : null;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withQuery($query) {
        $new = clone($this);
        $new->query = $query;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withScheme($scheme) {
        $new = clone($this);
        $new->scheme = $scheme;
        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withUserInfo($user, $password = null) {
        $new = clone($this);
        $new->userInfo = isset($password) ? "{$user}:{$password}" : $user;
        return $new;
    }

    /**
     * Utility if you just have a URL string
     *
     * @param string $url
     * @return static
     */
    public function withFullURL(string $url) {
        $info = parse_url($url);
        return $this
            ->withScheme($info["scheme"])
            ->withHost($info["host"])
            ->withPort($info["port"])
            ->withUserInfo($info["user"], $info["pass"])
            ->withPath($info["path"])
            ->withQuery($info["query"])
            ->withFragment($info["fragment"]);
    }
}
