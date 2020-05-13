<?php
declare(strict_types=1);
namespace Karthus\Http;

use Karthus\Http\Message\ServerRequest;
use Karthus\Http\Message\Stream;
use Karthus\Http\Message\UploadFile;
use Karthus\Http\Message\Uri;
use Swoole\Http\Request as SWRequest;

class Request extends ServerRequest{
    /**
     * @var SWRequest
     */
    private $request;

    /**
     * Request constructor.
     *
     * @param SWRequest|null $request
     */
    public function __construct(SWRequest $request = null) {
        $this->request  = $request;
        $this->initHeaders();
        // 将协议进行简单的格式化
        $protocol       = str_replace('HTTP/', '', $request->server['server_protocol']);
        // 为单元测试准备
        if($request->fd){
            $body       = new Stream($request->rawContent());
        }else{
            $body       = new Stream('');
        }
        $uri            = $this->initUri();
        $files          = $this->initFiles();
        $method         = strtoupper($request->server['request_method']);
        parent::__construct($method, $uri, null, $body, $protocol, $request->server);

        $this->withCookieParams($this->initCookie())
            ->withQueryParams($this->initGet())
            ->withParsedBody($this->initPost())
            ->withUploadedFiles($files);
    }

    /**
     * @param mixed ...$key
     * @return array|mixed
     */
    public function getRequestParam(...$key) {
        $data = array_merge($this->getParsedBody(), $this->getQueryParams());;
        if(empty($key)){
            return $data;
        }else{
            $res = [];
            foreach ($key as $item){
                $res[$item] = $data[$item] ?? null;
            }
            if(count($key) == 1){
                return array_shift($res);
            }else{
                return $res;
            }
        }
    }

    /**
     * @return SWRequest
     */
    public function getSwooleRequest() {
        return $this->request;
    }

    /**
     * 初始化URL信息
     *
     * @return Uri
     */
    private function initUri() {
        $query  = $this->request->server['query_string'] ?? '';
        $uri    = new Uri();
        $uri->withScheme('http')
            ->withPath($this->request->server['path_info'])
            ->withQuery($query);

        //host与port以header为准，防止经过proxy
        if(isset($this->request->header['host'])){
            $host       = $this->request->header['host'];
            $host       = explode(":",$host);
            $realHost   = $host[0];
            $port       = $host[1] ?? 80;
        }else{
            $realHost   = '127.0.0.1';
            $port       = $this->request->server['server_port'];
        }

        $uri->withHost($realHost);
        $uri->withPort($port);
        return $uri;
    }

    /**
     * 初始化HEADER头
     */
    private function initHeaders() {
        $headers = $this->request->header ?? [];
        foreach ($headers as $header => $val){
            $this->withAddedHeader($header,$val);
        }
    }

    /**
     * 初始化文件头
     *
     * @return array
     */
    private function initFiles() {
        if(isset($this->request->files)){
            $normalized = [];
            foreach($this->request->files as $key => $value){
                if(is_array($value) && !isset($value['tmp_name'])){
                    $normalized[$key] = [];
                    foreach($value as $file){
                        $normalized[$key][] = $this->initFile($file);
                    }
                    continue;
                }
                $normalized[$key] = $this->initFile($value);
            }
            return $normalized;
        }else{
            return [];
        }
    }

    /**
     * 上传文件？？
     *
     * @param array $file
     * @return UploadFile
     */
    private function initFile(array $file) {
        return new UploadFile(
            $file['tmp_name'],
            (int) $file['size'],
            (int) $file['error'],
            $file['name'],
            $file['type']
        );
    }

    /**
     * 初始化Cookies
     *
     * @return array
     */
    private function initCookie() {
        return $this->request->cookie ?? [];
    }

    /**
     * 初始化POST
     *
     * @return array
     */
    private function initPost() {
        return $this->request->post ?? [];
    }

    /**
     * 初始化GET
     *
     * @return array
     */
    private function initGet() {
        return $this->request->get ?? [];
    }

    /**
     * @return string
     */
    final public function __toString():string {
        return "";
    }

    /**
     * 销毁
     */
    public function __destruct() {
        $this->getBody()->close();
    }


    /**
     * 获取远程IP
     *
     * @return string
     */
    public function getRemoteIP(): string {
        $remoteIp = $this->request->header['x-real-ip'] ?? $this->request->server['remote_addr'];
        $remoteIp = strval($remoteIp);

        return $remoteIp;
    }

    /**
     * 获取Remote-UID
     *
     * @return int
     */
    public function getRemoteUserID(): int{
        $userID = $this->request->header['x-remote-userid'] ?? 0;

        return $userID;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string {
        $userAgent = $this->request->header['user-agent'] ?? '';

        return $userAgent;
    }

    /**
     * @return string
     */
    public function getAcceptLanguage(): string {
        $acceptLanguage = $this->request->header['accept-language'] ?? 'en-us';

        return $acceptLanguage;
    }

    /**
     * @return string
     */
    public function getRemoteAddr(): string {
        $remoteAddr     = $this->request->server['remote_addr'] ?? '';
        $remoteAddr     = strval($remoteAddr);

        return $remoteAddr;
    }

    /**
     * 获取Request-ID
     *
     * @return string
     */
    public function getRequestID(): string {
        $requestID = $this->request->header['x-request-id'] ?? '-';

        return $requestID;
    }

    /**
     * @return int
     */
    public function getRequestTime(): int{
        $requestID = $this->request->server['request_time'] ?? 0;

        return intval($requestID);
    }

    /**
     * @return float
     */
    public function getRequestTimeFloat(): float {
        $requestID = $this->request->server['request_time_float'] ?? 0;

        return floatval($requestID);
    }

    /**
     * 获取content-type
     *
     * @return string
     */
    public function getContentType(): string{
        $contentType = $this->request->header['content-type'] ?? '';

        return $contentType;
    }
}
