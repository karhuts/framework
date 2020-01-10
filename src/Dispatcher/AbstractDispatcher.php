<?php
declare(strict_types=1);

namespace Karthus\Dispatcher;

use Karthus\Contract\DispatcherInterface;

abstract class AbstractDispatcher implements DispatcherInterface {
    /**
     * @param array ...$params
     * @return
     */
    public function dispatch(...$params) {
        return $this->handle(...$params);
    }
}
