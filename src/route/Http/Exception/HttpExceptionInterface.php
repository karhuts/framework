<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  min@bluecity.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\route\Http\Exception;

use Psr\Http\Message\ResponseInterface;

interface HttpExceptionInterface
{
    public function buildJsonResponse(ResponseInterface $response): ResponseInterface;

    public function getHeaders(): array;

    public function getStatusCode(): int;
}
