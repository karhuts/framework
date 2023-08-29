<?php
declare(strict_types=1);
namespace karthus\route\Http\Exception;

use Psr\Http\Message\ResponseInterface;

interface HttpExceptionInterface
{

    public function buildJsonResponse(ResponseInterface $response): ResponseInterface;
    public function getHeaders(): array;
    public function getStatusCode(): int;
}
