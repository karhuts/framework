<?php
declare(strict_types=1);

namespace Karthus\Contract;

interface DispatcherInterface {
    public function dispatch(...$params);
}
