<?php
declare(strict_types=1);

namespace Karthus\Contract;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

interface OnRequestInterface {
    public function onRequest(SwooleRequest $request, SwooleResponse $response): void;
}
