<?php
declare(strict_types=1);
namespace Karthus\Logger;

/**
 * Interface LoggerInterface
 *
 * @package Karthus\Logger
 */
interface LoggerInterface {
    public const LOG_LEVEL_INFO = 1;
    public const LOG_LEVEL_NOTICE = 2;
    public const LOG_LEVEL_WARNING = 3;
    public const LOG_LEVEL_ERROR = 4;

    /**
     * 记录日志
     *
     * @param string|null $msg
     * @param int         $logLevel
     * @param string      $category
     * @return string
     */
    function logger(?string $msg,int $logLevel = self::LOG_LEVEL_INFO,
                 string $category = 'DEBUG'):string ;

    /**
     * 输出日志
     *
     * @param string|null $msg
     * @param int         $logLevel
     * @param string      $category
     * @return mixed
     */
    function console(?string $msg,int $logLevel = self::LOG_LEVEL_INFO,
                     string $category = 'DEBUG');
}
