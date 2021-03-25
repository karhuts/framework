<?php
declare(strict_types=1);
namespace Karthus\Http\Message;

use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends Request implements ServerRequestInterface {
    private $attributes = [];
    private $cookieParams = [];
    private $parsedBody = [];
    private $queryParams = [];
    private $serverParams;
    private $uploadedFiles = [];

    /**
     * ServerRequest constructor.
     *
     * @param string      $method
     * @param Uri|null    $uri
     * @param array|null  $headers
     * @param Stream|null $body
     * @param string      $protocolVersion
     * @param array       $serverParams
     */
    public function __construct(string $method = 'GET',
                                Uri $uri = null,
                                array $headers = null,
                                Stream $body = null,
                                string $protocolVersion = '1.1',
                                array $serverParams = array()) {
        $this->serverParams = $serverParams;
        parent::__construct($method, $uri, $headers, $body, $protocolVersion);
    }

    /**
     * 获取服务器参数
     *
     * @return array
     */
    public function getServerParams() {
        return $this->serverParams;
    }

    /**
     * 获取COOKIES
     *
     * @param null $name
     * @return array|mixed|null
     */
    public function getCookieParams($name = null) {
        if($name === null){
            return $this->cookieParams;
        }

        if(isset($this->cookieParams[$name])){
            return $this->cookieParams[$name];
        }

        return null;
    }

    /**
     * 设置COOKIES
     *
     * @param array $cookies
     * @return $this|ServerRequestInterface
     */
    public function withCookieParams(array $cookies) {
        $this->cookieParams = $cookies;
        return $this;
    }

    /**
     * 获取REQUEST参数
     *
     * @return array
     */
    public function getQueryParams() {
        return $this->queryParams;
    }

    /**
     * 获取具体request参数
     *
     * @param string $name
     * @param null   $default
     * @return mixed|null
     */
    public function getQueryParam(string $name, $default = null){
        $data = $this->getQueryParams();
        return $data[$name] ?? $default;
    }

    /**
     * @param array $query
     * @return $this|ServerRequest
     */
    public function withQueryParams(array $query) {
        $this->queryParams = $query;
        return $this;
    }

    /**
     * @return array
     */
    public function getUploadedFiles() {
        return $this->uploadedFiles;
    }

    /*
     * 适配二维数组方式上传
     */
    public function getUploadedFile($name) {
        return $this->uploadedFiles[$name] ?? null;
    }

    /**
     * @param array $uploadedFiles must be array of UploadFile Instance
     * @return ServerRequest
     */
    public function withUploadedFiles(array $uploadedFiles) {
        $this->uploadedFiles = $uploadedFiles;
        return $this;
    }

    /**
     * @param null $name
     * @return array|mixed|object|null
     */
    public function getParsedBody($name = null) {
        if($name !== null){
            return $this->parsedBody[$name] ?? null;
        }

        return $this->parsedBody;
    }

    /**
     * @param array|object|null $data
     * @return $this|ServerRequest
     */
    public function withParsedBody($data) {
        $this->parsedBody = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param null   $default
     * @return mixed|null
     */
    public function getAttribute($name, $default = null) {
        if (false === array_key_exists($name, $this->attributes)) {
            return $default;
        }
        return $this->attributes[$name];
    }

    public function withAttribute($name, $value) {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return $this|ServerRequestInterface
     */
    public function withoutAttribute($name) {
        if (false === array_key_exists($name, $this->attributes)) {
            return $this;
        }
        unset($this->attributes[$name]);
        return $this;
    }
}
