<?php
declare(strict_types=1);
namespace Karthus\Service;

use Karthus\Contract\RequestInterface;
use Karthus\Functions\Arr;
use Karthus\Functions\Strings;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use SplFileInfo;

/**
 * Class Request
 *
 * @package Service
 */
class Request implements RequestInterface{
    /**
     * @var array the keys to identify the data of request in coroutine context
     */
    protected $contextkeys = [
            'parsedData' => 'http.request.parsedData',
        ];

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        return $this->getRequestProperty($name);
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function __set($name, $value)
    {
        return $this->storeRequestProperty($name, $value);
    }

    /**
     * Retrieve the data from query parameters, if $key is null, will return all query parameters.
     *
     * @param mixed $default
     * @return array
     */
    public function query(?string $key = null, $default = null) {
        if ($key === null) {
            return $this->getQueryParams();
        }
        return data_get($this->getQueryParams(), $key, $default);
    }

    /**
     * Retrieve the data from route parameters.
     *
     * @param mixed $default
     * @return mixed|null
     */
    public function route(string $key, $default = null) {
        $route = $this->getAttribute(Dispatched::class);
        if (is_null($route)) {
            return $default;
        }
        return array_key_exists($key, $route->params) ? $route->params[$key] : $default;
    }

    /**
     * Retrieve the data from parsed body, if $key is null, will return all parsed body.
     *
     * @param mixed $default
     * @return array|mixed|object|null
     */
    public function post(?string $key = null, $default = null) {
        if ($key === null) {
            return $this->getParsedBody();
        }
        return data_get($this->getParsedBody(), $key, $default);
    }

    /**
     * Retrieve the input data from request, include query parameters, parsed body and json body,
     * if $key is null, will return all the parameters.
     *
     * @param mixed $default
     * @return array|mixed
     */
    public function input(string $key, $default = null) {
        $data = $this->getInputData();
        return data_get($data, $key, $default);
    }

    /**
     * Retrieve the input data from request via multi keys, include query parameters, parsed body and json body.
     *
     * @param mixed $default
     * @return array
     */
    public function inputs(array $keys, $default = null): array {
        $data = $this->getInputData();
        foreach ($keys as $key) {
            $result[$key] = data_get($data, $key, $default[$key] ?? null);
        }
        return $result;
    }

    /***
     * @return array
     */
    public function all(): array {
        $data = $this->getInputData();
        return $data ?? [];
    }

    /**
     * Determine if the $keys is exist in parameters.
     *
     * @param array $keys
     * @return array []array [found, not-found]
     */
    public function hasInput(array $keys): array {
        $data = $this->getInputData();
        $found = [];
        foreach ($keys as $key) {
            if (Arr::has($data, $key)) {
                $found[] = $key;
            }
        }
        return [
            $found,
            array_diff($keys, $found),
        ];
    }

    /**
     * Determine if the $keys is exist in parameters.
     *
     * @param array|string $keys
     * @return bool
     */
    public function has($keys): bool {
        return Arr::has($this->getInputData(), $keys);
    }

    /**
     * Retrieve the data from request headers.
     *
     * @param mixed $default
     * @return mixed|string|null
     */
    public function header(string $key, $default = null) {
        if (! $this->hasHeader($key)) {
            return $default;
        }
        return $this->getHeaderLine($key);
    }
    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path() {
        $pattern = trim($this->getPathInfo(), '/');
        return $pattern == '' ? '/' : $pattern;
    }
    /**
     * Returns the path being requested relative to the executed script.
     * The path info always starts with a /.
     * Suppose this request is instantiated from /mysite on localhost:
     *  * http://localhost/mysite              returns an empty string
     *  * http://localhost/mysite/about        returns '/about'
     *  * http://localhost/mysite/enco%20ded   returns '/enco%20ded'
     *  * http://localhost/mysite/about?var=1  returns '/about'.
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getPathInfo(): string {
        if ($this->pathInfo === null) {
            $this->pathInfo = $this->preparePathInfo();
        }
        return $this->pathInfo ?? '';
    }

    /**
     * Determine if the current request URI matches a pattern.
     *
     * @param mixed ...$patterns
     * @return bool
     */
    public function is(...$patterns): bool {
        foreach ($patterns as $pattern) {
            if (Strings::is($pattern, $this->decodedPath())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function decodedPath(): string {
        return rawurldecode($this->path());
    }

    /**
     * @return false|mixed|string|null
     */
    public function getRequestUri() {
        if ($this->requestUri === null) {
            $this->requestUri = $this->prepareRequestUri();
        }
        return $this->requestUri;
    }


    /**
     * Get the URL (no query string) for the request.
     */
    public function url(): string {
        return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
    }


    /**
     * @return string
     */
    public function fullUrl(): string {
        $query = $this->getQueryString();
        return $this->url() . '?' . $query;
    }
    /**
     * Generates the normalized query string for the Request.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized
     * and have consistent escaping.
     *
     * @return null|string A normalized query string for the Request
     */
    public function getQueryString(): ?string {
        $qs = static::normalizeQueryString($this->getServerParams()['query_string'] ?? '');
        return $qs === '' ? null : $qs;
    }

    /**
     * Normalizes a query string.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized,
     * have consistent escaping and unneeded delimiters are removed.
     *
     * @param string $qs Query string
     * @return string A normalized query string for the Request
     */
    public function normalizeQueryString(string $qs): string {
        if ($qs == '') {
            return '';
        }
        parse_str($qs, $qs);
        ksort($qs);
        return http_build_query($qs, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Retrieve a cookie from the request.
     *
     * @param null|mixed $default
     * @return array|mixed
     */
    public function cookie(string $key, $default = null) {
        return data_get($this->getCookieParams(), $key, $default);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasCookie(string $key): bool {
        return ! is_null($this->cookie($key));
    }

    /**
     * Retrieve a server variable from the request.
     *
     * @param null|mixed $default
     * @return null|array|string
     */
    public function server(string $key, $default = null) {
        return data_get($this->getServerParams(), $key, $default);
    }

    /**
     * Checks if the request method is of specified type.
     *
     * @param string $method Uppercase request method (GET, POST etc)
     * @return bool
     */
    public function isMethod(string $method): bool {
        return $this->getMethod() === strtoupper($method);
    }


    /**
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool {
        if ($file = $this->file($key)) {
            return $this->isValidFile($file);
        }
        return false;
    }

    /**
     * @return string
     */
    public function getProtocolVersion() {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $version
     * @return RequestInterface
     */
    public function withProtocolVersion($version) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array
     */
    public function getHeaders(): array {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return string[]
     */
    public function getHeader($name) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string          $name
     * @param string|string[] $value
     * @return RequestInterface
     */
    public function withHeader($name, $value) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string          $name
     * @param string|string[] $value
     * @return RequestInterface
     */
    public function withAddedHeader($name, $value) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return RequestInterface
     */
    public function withoutHeader($name) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return StreamInterface
     */
    public function getBody() {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param StreamInterface $body
     * @return RequestInterface
     */
    public function withBody(StreamInterface $body) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string
     */
    public function getRequestTarget()
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param mixed $requestTarget
     * @return RequestInterface|mixed
     */
    public function withRequestTarget($requestTarget) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string
     */
    public function getMethod(): string {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $method
     * @return RequestInterface|mixed
     */
    public function withMethod($method) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param UriInterface $uri
     * @param bool         $preserveHost
     * @return RequestInterface|mixed
     */
    public function withUri(UriInterface $uri, $preserveHost = false) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array|mixed
     */
    public function getServerParams() {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array|mixed
     */
    public function getCookieParams() {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /***
     * @param array $cookies
     * @return RequestInterface|mixed
     */
    public function withCookieParams(array $cookies) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array|mixed
     */
    public function getQueryParams() {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /***
     * @param array $query
     * @return RequestInterface|mixed
     */
    public function withQueryParams(array $query) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array|mixed
     */
    public function getUploadedFiles() {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $uploadedFiles
     * @return RequestInterface|mixed
     */
    public function withUploadedFiles(array $uploadedFiles) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array|mixed|object|null
     */
    public function getParsedBody() {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param array|object|null $data
     * @return RequestInterface|mixed
     */
    public function withParsedBody($data) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array|mixed
     */
    public function getAttributes() {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @param null   $default
     * @return mixed
     */
    public function getAttribute($name, $default = null) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @return RequestInterface|mixed
     */
    public function withAttribute($name, $value) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return RequestInterface|mixed
     */
    public function withoutAttribute($name) {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * Check that the given file is a valid SplFileInfo instance.
     *
     * @param mixed $file
     * @return bool
     */
    protected function isValidFile($file): bool {
        return $file instanceof SplFileInfo && $file->getPath() !== '';
    }

    /**
     * @return string
     */
    protected function preparePathInfo(): string {
        if (($requestUri = $this->getRequestUri()) === null) {
            return '/';
        }
        // Remove the query string from REQUEST_URI
        if (false !== $pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        if ($requestUri !== '' && $requestUri[0] !== '/') {
            $requestUri = '/' . $requestUri;
        }
        return (string) $requestUri;
    }

    /**
     * @return false|mixed|string
     */
    protected function prepareRequestUri() {
        $requestUri = '';
        $serverParams = $this->getServerParams();
        if (isset($serverParams['request_uri'])) {
            $requestUri = $serverParams['request_uri'];
            if ($requestUri !== '' && $requestUri[0] === '/') {
                // To only use path and query remove the fragment.
                if (false !== $pos = strpos($requestUri, '#')) {
                    $requestUri = substr($requestUri, 0, $pos);
                }
            } else {
                // HTTP proxy reqs setup request URI with scheme and host [and port] + the URL path,
                // only use URL path.
                $uriComponents = parse_url($requestUri);
                if (isset($uriComponents['path'])) {
                    $requestUri = $uriComponents['path'];
                }
                if (isset($uriComponents['query'])) {
                    $requestUri .= '?' . $uriComponents['query'];
                }
            }
        }
        // normalize the request URI to ease creating sub-requests from this request
        $serverParams['request_uri'] = $requestUri;
        return $requestUri;
    }

    /**
     * @return array
     */
    protected function getInputData(): array {
        return $this->storeParsedData(function () {
            $request = $this->getRequest();
            if (is_array($request->getParsedBody())) {
                $data = $request->getParsedBody();
            } else {
                $data = [];
            }
            return array_merge($data, $request->getQueryParams());
        });
    }

    /**
     * @param callable $callback
     * @return mixed|null
     */
    protected function storeParsedData(callable $callback) {
        if (! Context::has($this->contextkeys['parsedData'])) {
            return Context::set($this->contextkeys['parsedData'], call($callback));
        }
        return Context::get($this->contextkeys['parsedData']);
    }

    /**
     * @param string $key
     * @param        $value
     * @return $this
     */
    protected function storeRequestProperty(string $key, $value): self {
        Context::set(__CLASS__ . '.properties.' . $key, value($value));
        return $this;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    protected function getRequestProperty(string $key) {
        return Context::get(__CLASS__ . '.properties.' . $key);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    protected function call($name, $arguments) {
        $request = $this->getRequest();
        if (! method_exists($request, $name)) {
            throw new \RuntimeException('Method not exist.');
        }
        return $request->{$name}(...$arguments);
    }

    /**
     * @return ServerRequestInterface
     */
    protected function getRequest(): ServerRequestInterface {
        return Context::get(ServerRequestInterface::class);
    }
}
