<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  294953530@qq.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

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
            'Allow' => implode(', ', $allowed),
        ];

        parent::__construct(405, $message, $previous, $headers, $code);
    }
}
