<?php
declare(strict_types=1);

namespace Karthus\Http\Server;

use Karthus\Contract\Able\Sendable;
use Karthus\Contract\FileInterface;
use Karthus\Http\Stream\SwooleStream;

class Response extends \Karthus\Http\Base\Request implements Sendable {
    /**
     * @var null|\Swoole\Http\Response
     */
    protected $swooleResponse;
    /**
     * @var array
     */
    protected $cookies = [];

    /**
     * Response constructor.
     *
     * @param \Swoole\Http\Response|null $response
     */
    public function __construct(\Swoole\Http\Response $response = null) {
        $this->swooleResponse = $response;
    }
    /**
     * Handle response and send.
     */
    public function send() {
        if (! $this->getSwooleResponse()) {
            return;
        }
        $this->buildSwooleResponse($this->swooleResponse, $this);
        $content = $this->getBody();
        if ($content instanceof FileInterface) {
            return $this->swooleResponse->sendfile($content->getFilename());
        }
        $this->swooleResponse->end($content->getContents());
    }
    /**
     * Returns an instance with body content.
     */
    public function withContent(string $content): self {
        $new = clone $this;
        $new->stream = new SwooleStream($content);
        return $new;
    }

    /**
     * Return all cookies.
     */
    public function getCookies(): array {
        return $this->cookies;
    }

    /**
     * @return \Swoole\Http\Response|null
     */
    public function getSwooleResponse(): ?\Swoole\Http\Response {
        return $this->swooleResponse;
    }

    /**
     * @param \Swoole\Http\Response $swooleResponse
     * @return $this
     */
    public function setSwooleResponse(\Swoole\Http\Response $swooleResponse): self {
        $this->swooleResponse = $swooleResponse;
        return $this;
    }

    /**
     * Keep this method at public level,
     * allows the proxy class to override this method,
     * or override the method that used this method.
     *
     * @param \Swoole\Http\Response $swooleResponse
     * @param Response              $response
     */
    public function buildSwooleResponse(\Swoole\Http\Response $swooleResponse, Response $response): void {
        /*
         * Headers
         */
        foreach ($response->getHeaders() as $key => $value) {
            $swooleResponse->header($key, implode(';', $value));
        }

        /*
         * Status code
         */
        $swooleResponse->status($response->getStatusCode());
    }
}
