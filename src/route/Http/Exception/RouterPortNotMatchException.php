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

class RouterPortNotMatchException extends Exception
{
    public function __construct(
        string $message = 'Router Request Port Not Match',
        ?Exception $previous = null,
        int $code = 0
    ) {
        parent::__construct(404, $message, $previous, [], $code);
    }
}
