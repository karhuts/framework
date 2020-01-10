<?php
declare(strict_types=1);

namespace Karthus\Contract;

use Karthus\Functions\Propagation;
use Throwable;

abstract class ExceptionHandler {
    /**
     * Handle the exception, and return the specified result.
     *
     * @param Throwable         $throwable
     * @param ResponseInterface $response
     */
    abstract public function handle(Throwable $throwable, ResponseInterface $response);

    /**
     * Determine if the current exception handler should handle the exception,.
     *
     * @param Throwable $throwable
     * @return bool
     *              If return true, then this exception handler will handle the exception,
     *              If return false, then delegate to next handler
     */
    abstract public function isValid(Throwable $throwable): bool;

    /**
     * Stop propagate the exception to next handler.
     */
    public function stopPropagation(): bool {
        Propagation::instance()->setPropagationStopped(true);
        return true;
    }
    /**
     * Is propagation stopped ?
     * This will typically only be used by the handler to determine if the
     * provious handler halted propagation.
     */
    public function isPropagationStopped(): bool {
        return Propagation::instance()->isPropagationStopped();
    }
}
