<?php
declare(strict_types=1);
namespace karthus\route\Http\Exception;

class MethodNotAllowedException extends Exception
{
    public function __construct(
        array $allowed = [],
        string $message = 'Method Not Allowed',
        ?Exception $previous = null,
        int $code = 0
    ) {
        $headers = [
            'Allow' => implode(', ', $allowed)
        ];

        parent::__construct(405, $message, $previous, $headers, $code);
    }
}
