<?php
declare(strict_types=1);

namespace karthus\route\Http\Exception;
class NotFoundException extends Exception
{
    public function __construct(string $message = 'Not Found', ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(404, $message, $previous, [], $code);
    }
}
