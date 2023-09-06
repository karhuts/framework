<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  294953530@qq.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\route\Http\Exception;

use Psr\Http\Message\ResponseInterface;

class Exception extends \Exception implements HttpExceptionInterface
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var string
     */
    protected $message;

    /**
     * @var int
     */
    protected $status;

    public function __construct(
        int $status,
        string $message = null,
        \Exception $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        $this->headers = $headers;
        $this->message = $message;
        $this->status = $status;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function buildJsonResponse(ResponseInterface $response): ResponseInterface
    {
        $this->headers['content-type'] = 'application/json';

        foreach ($this->headers as $key => $value) {
            /** @var ResponseInterface $response */
            $response = $response->withAddedHeader($key, $value);
        }

        if ($response->getBody()->isWritable()) {
            $response->getBody()->write(json_encode([
                'status_code' => $this->status,
                'reason_phrase' => $this->message,
            ]));
        }

        return $response->withStatus($this->status, $this->message);
    }
}
