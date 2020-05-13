<?php
declare(strict_types=1);
namespace Karthus\Http\Client;

use Karthus\Exception\Exception;
use Karthus\Http\Client\Bean\Response;
use Karthus\Http\Client\Bean\Url;
use Swoole\Coroutine\Http\Client;

class HttpClient {
    /**
     * 协程客户端
     * @var Client
     */
    protected $client;

    /**
     * 协程客户端设置项
     * @var array
     */
    protected $setting = [];

    /**
     * @var Url;
     */
    private $url;

    /**
     * 强制开启SSL请求
     * @var bool
     */
    protected $enableSSL = false;

    /**
     * Header头
     *
     * @var array
     */
    protected $header = [];

    /**
     * 请求携带的Cookies
     * @var array
     */
    protected $cookies = [];

    /**
     * HttpClient constructor.
     *
     * @param string $url
     * @param int    $timeout
     * @param int    $connectTimeout
     * @throws \ReflectionException
     */
    public function __construct(string $url, int $timeout = 3, int $connectTimeout = 5) {
        $this->setUrl($url);
        $this->setTimeout($timeout);
        $this->setConnectTimeout($connectTimeout);
    }

    /**
     * @param string $url
     * @return HttpClient
     * @throws \ReflectionException
     */
    public function setUrl(string $url): HttpClient{
        if ($url instanceof Url){
            $this->url = $url;
            return $this;
        }
        $info       = parse_url($url);
        if (empty($info['scheme'])) {
            // 防止无scheme导致的host解析异常 默认作为http处理
            $info   = parse_url('//' . $url);
        }
        $this->url  = new Url($info);
        if (empty($this->url->getHost())) {
            throw new Exception("HttpClient: {$url} is invalid");
        }
        return $this;
    }

    /**
     * 请求超时时间
     *
     * @param int $timeout
     * @return HttpClient
     */
    public function setTimeout(int $timeout = 3): HttpClient {
        $this->setting['timeout'] = intval($timeout);
        return $this;
    }

    /**
     * 连接超时时间
     *
     * @param int $connectTimeout
     * @return HttpClient
     */
    public function setConnectTimeout(int $connectTimeout = 5): HttpClient {
        $this->setting['connect_timeout'] = intval($connectTimeout);
        return $this;
    }

    /**
     * 启用或关闭HTTP长连接
     * @param bool $keepAlive 是否开启长连接
     * @return $this
     */
    public function setKeepAlive(bool $keepAlive = true) : HttpClient{
        $this->setting['keep_alive'] = $keepAlive;
        return $this;
    }

    /**
     * @param string $userAgent
     * @return HttpClient
     */
    public function setUserAgent(string $userAgent = ''): HttpClient {
        $this->setHeader('User-Agent', $userAgent);
        return $this;
    }

    /**
     * 设置AcceptLanguage
     *
     * @param string $acceptLanguage
     * @return HttpClient
     */
    public function setAcceptLanguage(string $acceptLanguage = 'zh-cn'): HttpClient {
        $this->setHeader('Accept-Language', $acceptLanguage);
        return $this;
    }

    /**
     * 设置header头中的X-Remote-UserID
     *
     * @param int $uid
     * @return HttpClient
     */
    public function setRemoteUID(int $uid): HttpClient{
        $this->setHeader('X-Remote-UserID', (string) $uid);
        return $this;
    }

    /**
     * 设置requestID
     *
     * @param string $requestID
     * @return HttpClient
     */
    public function setRequestID(string $requestID): HttpClient {
        $this->setHeader('X-Request-ID', $requestID);
        return $this;
    }



    /**
     * 直接设置客户端配置
     * @param string $key 配置key值
     * @param mixed $setting 配置value值
     * @return HttpClient
     */
    public function setSetting(string $key, $setting): HttpClient {
        $this->setting[$key] = $setting;
        return $this;
    }

    /**
     * @param string $userName
     * @param string $password
     * @return HttpClient
     */
    public function setBasicAuth(string $userName, string $password): HttpClient {
        $basicAuthToken = base64_encode("{$userName}:{$password}");
        $this->setHeader('Authorization', "Basic {$basicAuthToken}", false);
        return $this;
    }

    /**
     * 设置请求头集合
     * @param array $header
     * @param bool $isMerge
     * @param bool strtolower
     * @return HttpClient
     */
    public function setHeaders(array $header, $isMerge = true, $strtolower = true): HttpClient {
        if (empty($header)) {
            return $this;
        }

        // 非合并模式先清空当前的Header再设置
        if (!$isMerge) {
            $this->header = [];
        }

        foreach ($header as $name => $value) {
            $this->setHeader($name, $value, $strtolower);
        }
        return $this;
    }

    /**
     * 设置单个请求头
     * 根据 RFC 请求头不区分大小写 会全部转成小写
     * @param string $key
     * @param string $value
     * @param bool strtolower
     * @return HttpClient
     */
    public function setHeader(string $key, string $value, bool $strtolower = true): HttpClient {
        if($strtolower){
            $this->header[strtolower($key)] = strtolower($value);
        }else{
            $this->header[$key] = $value;
        }
        return $this;
    }

    /***
     * 设置method
     *
     * @param string $method
     * @return HttpClient
     */
    public function setMethod(string $method = Method::METHOD_GET):HttpClient {
        $this->getClient()->setMethod($method);
        return $this;
    }

    /**
     * 设置携带的Cookie
     * @param string $key
     * @param string $value
     * @return HttpClient
     */
    public function addCookie(string $key, string $value): HttpClient {
        $this->cookies[$key] = $value;
        return $this;
    }

    /**
     * 获取携程client
     *
     * @return Client
     */
    public function getClient(): Client {
        if ($this->client instanceof Client) {
            $url    = $this->pathInfo();
            $this->client->host = $url->getHost();
            $this->client->port = $url->getPort();
            $this->client->ssl  = $url->getIsSsl();
            $this->client->set($this->setting);
            return $this->client;
        }
        $url = $this->pathInfo();
        $this->httpClient = new Client($url->getHost(), $url->getPort(), $url->getIsSsl());
        $this->client->set($this->setting);
        return $this->getClient();
    }

    /**
     * 解析当前的请求Url
     *
     * @param array|null $query
     * @return Url
     */
    protected function pathInfo(?array $query = null) :Url {
        // 请求时当前对象没有设置Url
        if (!($this->url instanceof Url)) {
            throw new Exception("HttpClient: Url is empty");
        }

        // 获取当前的请求参数
        $path   = $this->url->getPath();
        $host   = $this->url->getHost();
        $port   = $this->url->getPort();
        $query  = $this->url->getQuery();
        $scheme = strtolower($this->url->getScheme());
        if (empty($scheme)) {
            $scheme     = 'http';
        }
        // 支持的scheme
        $allowSchemes   = [
            'http'  => 80,
            'https' => 443,
            'ws'    => 80,
            'wss'   => 443
        ];

        // 只允许进行支持的请求
        if (!array_key_exists($scheme, $allowSchemes)) {
            throw new Exception("HttpClient: Clients are only allowed to initiate HTTP(WS) or HTTPS(WSS) requests");
        }

        // URL即使解析成功了也有可能存在HOST为空的情况
        if (empty($host)) {
            throw new Exception("HttpClient: Current URL is invalid because HOST is empty");
        }

        // 如果端口是空的 那么根据协议自动补全端口 否则使用原来的端口
        if (empty($port)) {
            $port = isset($allowSchemes[$scheme]) ? $allowSchemes[$scheme] : 80;
            $this->url->setPort($port);
        }

        // 如果当前是443端口 或者enableSSL 则开启SSL安全链接
        if ($this->enableSSL || $port === 443) {
            $this->url->setIsSsl(true);
        }

        // 格式化路径和查询参数
        $path   = empty($path) ? '/' : $path;
        $query  = empty($query) ? '' : '?' . $query;
        $this->url->setFullPath($path . $query);
        return $this->url;
    }

    /**
     * 设置为XMLHttpRequest请求
     * @return $this
     */
    public function setXMLHttpRequest() : HttpClient {
        $this->setHeader('x-requested-with', 'xmlhttprequest');
        return $this;
    }

    /**
     * 设置为Json请求
     * @return $this
     */
    public function setContentTypeJson() : HttpClient{
        $this->setContentType(ContentType::APPLICATION_JSON);
        return $this;
    }

    /**
     * 设置为Xml请求
     * @return $this
     */
    public function setContentTypeXml() : HttpClient{
        $this->setContentType(ContentType::APPLICATION_XML);
        return $this;
    }

    /**
     * 设置为FromData请求
     * @return $this
     */
    public function setContentTypeFormData() : HttpClient{
        $this->setContentType(ContentType::FORM_DATA);
        return $this;
    }

    /**
     * 设置为FromUrlencoded请求
     * @return $this
     */
    public function setContentTypeFormUrlencoded() :HttpClient{
        $this->setContentType(ContentType::X_WWW_FORM_URLENCODED);
        return $this;
    }

    /**
     * 设置ContentType
     * @param string $contentType
     * @return HttpClient
     */
    public function setContentType(string $contentType) {
        $this->setHeader('content-type', $contentType);
        return $this;
    }

    /**
     * 执行请求
     * 此模式下直接发送Raw数据需要手动组装
     * 请注意此方法会忽略设置的POST数据而使用参数传入的RAW数据
     *
     * @param string $httpMethod  请求使用的方法 默认为GET
     * @param null   $rawData     请注意如果需要发送JSON或XML需要自己先行编码
     * @param string $contentType 请求类型 默认不去设置
     * @return Response
     * @throws \ReflectionException
     */
    protected function execute(string $httpMethod = Method::METHOD_GET,
                                  $rawData = null,
                                  string $contentType = null): Response {
        $client = $this->getClient();
        //预处理。合并cookie 和header
        $this->setMethod($httpMethod);
        $client->setCookies((array) $this->cookies + (array)$client->cookies);
        if($httpMethod === Method::METHOD_POST){
            if(is_array($rawData)){
                foreach ($rawData as $key => $item){
                    if($item instanceof \CURLFile){
                        $client->addFile(
                            $item->getFilename(),
                            $key,
                            $item->getMimeType(),
                            $item->getPostFilename()
                        );
                        unset($rawData[$key]);
                    }
                }
                $client->setData($rawData);
            }elseif($rawData !== null){
                $client->setData($rawData);
            }
        }elseif($rawData !== null){
            $client->setData($rawData);
        }
        if(is_string($rawData)){
            $this->setHeader('Content-Length', (string) strlen($rawData));
        }
        if ($contentType) {
            $this->setContentType($contentType);
        }
        $client->setHeaders($this->header);
        $client->execute($this->url->getFullPath());
        // 如果不设置保持长连接则直接关闭当前链接
        if (!isset($this->clientSetting['keep_alive']) || $this->setting['keep_alive'] !== true) {
            $client->close();
        }
        // 处理重定向
        // TODO
        return $this->createHttpResponse($client);
    }


    /**
     * 发起GET请求
     * 设置的请求头会合并到本次请求中
     *
     * @param array $headers
     * @return Response
     * @throws \ReflectionException
     */
    public function get(array $headers = []): Response {
        return $this->setHeaders($headers)->execute(Method::METHOD_GET);
    }

    /**
     * 发起POST请求
     *
     * @param null  $data
     * @param array $headers
     * @return Response
     * @throws \ReflectionException
     */
    public function post($data = null, array $headers = []): Response {
        return $this->setHeaders($headers)->execute(Method::METHOD_POST, $data);
    }

    /**
     * 发起PUT请求
     *
     * @param null  $data
     * @param array $headers
     * @return Response
     * @throws \ReflectionException
     */
    public function put($data = null, array $headers = []): Response {
        return $this->setHeaders($headers)->execute(Method::METHOD_PUT, $data);
    }

    /**
     * 发起PATCH请求
     *
     * @param null  $data
     * @param array $headers
     * @return Response
     * @throws \ReflectionException
     */
    public function patch($data = null, array $headers = []): Response {
        return $this->setHeaders($headers)->execute(Method::METHOD_PATCH, $data);
    }

    /**
     * 发起预检请求
     *
     * @param null  $data
     * @param array $headers
     * @return Response
     * @throws \ReflectionException
     */
    public function options($data = null, array $headers = []): Response {
        return $this->setHeaders($headers)->execute(Method::METHOD_OPTIONS, $data);
    }


    /**
     * 发起TRACE请求
     *
     * @param array $headers
     * @return Response
     * @throws \ReflectionException
     */
    public function trace(array $headers = []): Response {
        return $this->setHeaders($headers)->execute(Method::METHOD_TRACE);
    }

    /**
     * 发起DELETE请求
     *
     * @param array $headers
     * @return Response
     * @throws \ReflectionException
     */
    public function delete(array $headers = []): Response {
        return $this->setHeaders($headers)->execute(Method::METHOD_DELETE);
    }

    /**
     * 发起HEAD请求
     *
     * @param array $headers
     * @return Response
     * @throws \ReflectionException
     */
    public function head(array $headers = []): Response {
        return $this->setHeaders($headers)->execute(Method::METHOD_HEAD);

    }


    /**
     * 文件下载直接落盘不走Body拼接更节省内存
     * 可以通过偏移量(offset=原文件字节数)实现APPEND的效果
     *
     * @param string $filename    文件保存到路径
     * @param int    $offset      写入偏移量 (设0时如文件已存在底层会自动清空此文件)
     * @param string $httpMethod  设置请求的HTTP方法
     * @param null   $rawData     设置请求数据
     * @param null   $contentType 设置请求类型
     * @return Response|false 当文件打开失败或feek失败时会返回false
     * @throws \ReflectionException
     */
    public function download(string $filename,
                             int $offset = 0,
                             string $httpMethod = Method::METHOD_GET,
                             $rawData = null,
                             string $contentType = '') {
        $client = $this->getClient();
        $client->setMethod($httpMethod);

        // 如果提供了数组那么认为是x-www-form-unlencoded快捷请求
        if (is_array($rawData)) {
            $rawData = http_build_query($rawData);
            $this->setContentTypeFormUrlencoded();
        }

        // 直接设置请求包体 (特殊格式的包体可以使用提供的Helper来手动构建)
        if (!empty($rawData)) {
            $client->setData($rawData);
            $this->setHeader('Content-Length', (string) strlen($rawData));
        }

        // 设置ContentType(如果未设置默认为空的)
        if ($contentType) {
            $this->setContentType($contentType);
        }

        $response = $client->download($this->url->getFullPath(), $filename, $offset);
        return $response ? $this->createHttpResponse($client) : false;
    }

    /**
     * 生成一个响应结构体
     *
     * @param Client $client
     * @return Response
     * @throws \ReflectionException
     */
    private function createHttpResponse(Client $client): Response {
        $response = new Response((array) $client);
        $response->setClient($client);
        return $response;
    }

    /**
     * 销毁
     */
    public function __destruct() {
        if($this->client instanceof Client){
            if($this->client->connected){
                $this->client->close();
            }
            $this->httpClient = null;
        }
    }
}
