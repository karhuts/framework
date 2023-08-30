<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  min@bluecity.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\route\Http;

class Constant
{
    public const API_CODE_OK = 200;

    public const API_CODE_CREATED = 201;

    public const API_CODE_NO_CONTENT = 204;

    public const API_CODE_BAD_REQUEST = 400;

    public const API_CODE_UNAUTHORIZED = 401;

    public const API_CODE_PAYMENT_REQUIRED = 402;

    public const API_CODE_FORBIDDEN = 403;

    public const API_CODE_NOT_FOUND = 404;

    public const API_CODE_METHOD_NOT_ALLOWED = 405;

    public const API_CODE_GONE = 410;

    public const API_CODE_UNSUPPORTED_MEDIA_TYPE = 415;

    public const API_CODE_UNPROCESSABLE_ENTITY = 422;

    public const API_CODE_TOO_MANY_REQUESTS = 429;

    public const API_CODE_INTERNAL_SERVER_ERROR = 500;
}
