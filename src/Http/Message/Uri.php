<?php
declare(strict_types=1);
namespace Karthus\Http\Message;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface {
    private $host;
    private $userInfo;
    private $port = 80;
    private $path;
    private $query;
    private $fragment;
    private $scheme;

    /**
     * Uri constructor.
     *
     * @param string $url
     */
    public function __construct($url = '') {
        $parts = parse_url($url);
        $this->scheme   = $parts['scheme'] ?? '';
        $this->userInfo = $parts['user'] ?? '';
        $this->host     = $parts['host'] ?? '';
        $this->port     = $parts['port'] ?? 80;
        $this->path     = $parts['path'] ?? '';
        $this->query    = $parts['query'] ?? '';
        $this->fragment = $parts['fragment'] ?? '';
        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    /**
     * 获取scheme
     *
     * @return string
     */
    public function getScheme(): string {
        return $this->scheme;
    }

    /**
     * 获取认证头
     *
     * @return mixed|string
     */
    public function getAuthority(): string {
        $authority = $this->host;
        if (!empty($this->userInfo)) {
            $authority = $this->userInfo . '@' . $authority;
        }
        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }
        return $authority;
    }

    /**
     * 获取用户信息
     *
     * @return string
     */
    public function getUserInfo(): string {
        return $this->userInfo;
    }

    /**
     * 获取host
     *
     * @return string
     */
    public function getHost(): string {
        return $this->host;
    }

    /**
     * 获取端口
     *
     * @return int|mixed|null
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * 获取Path
     *
     * @return mixed|string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * 获取Query
     *
     * @return mixed|string
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * 获取Fragment
     *
     * @return mixed|string
     */
    public function getFragment() {
        return $this->fragment;
    }

    /**
     * 设置协议头
     *
     * @param string $scheme
     * @return $this
     */
    public function withScheme($scheme): Uri{
        if ($this->scheme === $scheme) {
            return $this;
        }
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * 设置用户信息
     *
     * @param string $user
     * @param null   $password
     * @return $this
     */
    public function withUserInfo($user, $password = null): Uri{
        $info = $user;
        if ($password != '') {
            $info .= ':' . $password;
        }
        if ($this->userInfo === $info) {
            return $this;
        }
        $this->userInfo = $info;
        return $this;
    }

    /**
     * 设置Host
     *
     * @param string $host
     * @return $this
     */
    public function withHost($host): Uri{
        $host = strtolower($host);
        if ($this->host === $host) {
            return $this;
        }
        $this->host = $host;
        return $this;
    }

    /**
     * 设置端口
     *
     * @param int|null $port
     * @return $this
     */
    public function withPort($port): Uri{
        if ($this->port === $port) {
            return $this;
        }
        $this->port = $port;
        return $this;
    }

    /**
     * 设置Path
     *
     * @param string $path
     * @return $this
     */
    public function withPath($path): Uri{
        if ($this->path === $path) {
            return $this;
        }
        $this->path = $path;
        return $this;
    }

    /**
     * 设置Query参数
     *
     * @param string $query
     * @return $this|Uri
     */
    public function withQuery($query): Uri{
        if ($this->query === $query) {
            return $this;
        }
        $this->query = $query;
        return $this;
    }

    /**
     * 设置Fragment
     *
     * @param string $fragment
     * @return $this
     */
    public function withFragment($fragment): Uri {
        if ($this->fragment === $fragment) {
            return $this;
        }
        $this->fragment = $fragment;
        return $this;
    }

    /**
     * 格式化字符串
     *
     * @return string
     */
    public function __toString(): string {
        $uri = '';
        // weak type checks to also accept null until we can add scalar type hints
        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }
        if ($this->getAuthority() !== ''|| $this->scheme === 'file') {
            $uri .= '//' . $this->getAuthority();
        }
        $uri .= $this->path;
        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }
        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }
        return $uri;
    }
}
