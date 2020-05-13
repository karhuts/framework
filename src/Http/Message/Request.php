<?php
declare(strict_types=1);
namespace Karthus\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface {
    private $uri;
    private $method;
    private $target;

    /**
     * Request constructor.
     *
     * @param string      $method
     * @param Uri|null    $uri
     * @param array|null  $headers
     * @param Stream|null $body
     * @param string      $protocolVersion
     */
    public function __construct(string $method = 'GET',
                                Uri $uri = null,
                                array $headers = null,
                                Stream $body = null,
                                string $protocolVersion = '1.1') {
        $this->method = $method;
        if($uri != null){
            $this->uri = $uri;
        }
        parent::__construct($headers, $body, $protocolVersion);
    }

    /**
     * @return mixed|string
     */
    public function getRequestTarget() {
        if (!empty($this->target)) {
            return $this->target;
        }
        if($this->uri instanceof Uri){
            $target = $this->uri->getPath();
            if ($target == '') {
                $target = '/';
            }
            if ($this->uri->getQuery() != '') {
                $target .= '?' . $this->uri->getQuery();
            }
        }else{
            $target = "/";
        }
        return $target;
    }


    /**
     * @param mixed $requestTarget
     * @return $this|RequestInterface
     */
    public function withRequestTarget($requestTarget) {
        $this->target = $requestTarget;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @param string $method
     * @return $this|RequestInterface
     */
    public function withMethod($method) {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * @return Uri|UriInterface|null
     */
    public function getUri() {
        return $this->uri;
    }

    /**
     * @param UriInterface $uri
     * @param bool         $preserveHost
     * @return $this|RequestInterface
     */
    public function withUri(UriInterface $uri, $preserveHost = false) {
        if ($uri === $this->uri) {
            return $this;
        }
        $this->uri = $uri;
        if (!$preserveHost) {
            $host = $this->uri->getHost();
            if (!empty($host)) {
                if (($port = $this->uri->getPort()) !== null) {
                    $host .= ':' . $port;
                }
                if ($this->getHeader('host')) {
                    $header = $this->getHeader('host');
                } else {
                    $header = 'Host';
                }
                $this->withHeader($header,$host);
            }
        }
        return $this;
    }
}
