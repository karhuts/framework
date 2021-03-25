<?php
declare(strict_types=1);
namespace Karthus;

class SystemConst {
    public const SHUTDOWN_FUNCTION  = 'SHUTDOWN_FUNCTION';
    public const LOGGER_HANDLER     = 'LOGGER_HANDLER';
    public const ERROR_HANDLER      = 'ERROR_HANDLER';
    public const TRIGGER_HANDLER    = 'TRIGGER_HANDLER';
    public const KARTHUS_VERSION    = '2.0.31';

    public const HTTP_CONTROLLER_NAMESPACE      = 'HTTP_CONTROLLER_NAMESPACE';
    public const HTTP_CONTROLLER_MAX_DEPTH      = 'CONTROLLER_MAX_DEPTH';
    public const HTTP_CONTROLLER_POOL_MAX_NUM   = 'HTTP_CONTROLLER_POOL_MAX_NUM';
    public const HTTP_CONTROLLER_POOL_WAIT_TIME = 'HTTP_CONTROLLER_POOL_WAIT_TIME';
    public const HTTP_EXCEPTION_HANDLER         = 'HTTP_EXCEPTION_HANDLER';
}
