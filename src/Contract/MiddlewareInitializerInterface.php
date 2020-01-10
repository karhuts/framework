<?php
declare(strict_types=1);

namespace Karthus\Contract;

interface MiddlewareInitializerInterface {
    public function initCoreMiddleware(string $serverName): void;
}
