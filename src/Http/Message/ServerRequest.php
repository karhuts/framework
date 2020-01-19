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
    public function __construct(
        string $method = 'GET',
        Uri $uri = null,
        array $headers = null,
        Stream $body = null,
        string $protocolVersion = '1.1',
        array $serverParams = array()
    ) {
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
        }else{
            if(isset($this->cookieParams[$name])){
                return $this->cookieParams[$name];
            }else{
                return null;
            }
        }

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
     * @param $name
     * @return mixed|null
     */
    public function getQueryParam($name){
        $data = $this->getQueryParams();
        if(isset($data[$name])){
            return $data[$name];
        }else{
            return null;
        }
    }

    public function withQueryParams(array $query) {
        $this->queryParams = $query;
        return $this;
    }

    public function getUploadedFiles() {
        return $this->uploadedFiles;
    }

    /*
     * 适配二维数组方式上传
     */
    public function getUploadedFile($name) {
        if(isset($this->uploadedFiles[$name])){
            return $this->uploadedFiles[$name];
        }else{
            return null;
        }
    }

    /**
     * @param array $uploadedFiles must be array of UploadFile Instance
     * @return ServerRequest
     */
    public function withUploadedFiles(array $uploadedFiles) {
        $this->uploadedFiles = $uploadedFiles;
        return $this;
    }

    public function getParsedBody($name = null) {
        if($name !== null){
            if(isset($this->parsedBody[$name])){
                return $this->parsedBody[$name];
            }else{
                return null;
            }
        }else{
            return $this->parsedBody;
        }
    }

    public function withParsedBody($data) {
        $this->parsedBody = $data;
        return $this;
    }

    public function getAttributes() {
        return $this->attributes;
    }

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

    /**
     * @return string
     */
    public function getRemoteIP(): string {
        $remoteIp = $this->serverParams['x-real-ip'] ?? $this->serverParams['remote_addr'];
        $remoteIp = strval($remoteIp);

        return $remoteIp;
    }

    /**
     * 获取Remote-UID
     *
     * @return int
     */
    public function getRemoteUserID(): int{
        $userID = $this->serverParams['x-remote-userid'] ?? 0;

        return $userID;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string {
        $userAgent = $this->serverParams['user-agent'] ?? '';

        return $userAgent;
    }

    /**
     * @return string
     */
    public function getAcceptLanguage(): string {
        $acceptLanguage = $this->serverParams['accept-language'] ?? 'en-us';

        return $acceptLanguage;
    }

    /**
     * @return string
     */
    public function getRemoteAddr(): string {
        $remoteAddr     = $this->serverParams['remote_addr'] ?? '';
        $remoteAddr     = strval($remoteAddr);

        return $remoteAddr;
    }

    /**
     * 获取Request-ID
     *
     * @return string
     */
    public function getRequestID(): string {
        $requestID = $this->serverParams['x-request-id'] ?? '-';

        return $requestID;
    }

    /**
     * @return int
     */
    public function getRequestTime(): int{
        $requestID = $this->serverParams['request_time'] ?? 0;

        return intval($requestID);
    }

    /**
     * @return float
     */
    public function getRequestTimeFloat(): float {
        $requestID = $this->serverParams['request_time'] ?? 0;

        return floatval($requestID);
    }
}
