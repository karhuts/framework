<?php
declare(strict_types=1);

namespace Karthus\Exception;

use Karthus\Contract\ExceptionHandler;
use Karthus\Stream\SwooleStream;
use Karthus\Contract\ResponseInterface;
use Throwable;

class HttpExceptionHandler extends ExceptionHandler {

    /**
     * Handle the exception, and return the specified result.
     *
     * @param Throwable         $throwable
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handle(Throwable $throwable, ResponseInterface $response) {
        return $response->withStatus($throwable->getCode())->withBody(new SwooleStream($throwable->getMessage()));
    }


    /**
     * Determine if the current exception handler should handle the exception,.
     *
     * @return bool
     *              If return true, then this exception handler will handle the exception,
     *              If return false, then delegate to next handler
     */
    public function isValid(Throwable $throwable): bool {
        return true;
    }
}
