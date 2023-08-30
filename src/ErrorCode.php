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

namespace karthus;

class ErrorCode
{
    /**
     * @var array|string[]
     */
    public static array $MSG = [
        400 => '非法请求',
        401 => '验证失败,请重新登录',
        402 => '需付费',
        403 => '操作被禁止',
        404 => '未找到',
        405 => '请求的方法不支持',
        410 => '操作不被支持',
        415 => '请求错误',
        422 => '参数错误',
        429 => '操作过于频繁,请稍后再试',
        500 => '未知错误,请检查您的网络',
    ];
}
