<?php
declare(strict_types=1);
namespace Karthus\Console;

use Karthus\Exception\ErrorException;
use Karthus\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Class Error
 */
class Error {
    /**
     * @var int
     */
    public $level = E_ALL;
    /**
     * @var LoggerInterface
     */
    public $logger;
    /**
     * Error constructor.
     * @param int $level
     * @param LoggerInterface $logger
     */
    public function __construct(int $level, LoggerInterface $logger) {
        $this->level  = $level;
        $this->logger = $logger;
        $this->register();
    }
    /**
     * 注册错误处理
     */
    public function register() {
        // 设置错误级别
        $level = $this->level;
        if (error_reporting() !== $level) {
            error_reporting($level);
        }
        // 注册错误处理
        set_error_handler([$this, 'appError']);
        set_exception_handler([$this, 'appException']); // swoole 不支持该函数
        register_shutdown_function([$this, 'appShutdown']);
    }

    /**
     * 错误处理
     *
     * @param        $errno
     * @param        $errstr
     * @param string $errfile
     * @param int    $errline
     * @throws ErrorException
     */
    public function appError($errno, $errstr, $errfile = '', $errline = 0) {
        if (error_reporting() & $errno) {
            // 委托给异常处理
            if (static::isFatalWarning($errno, $errstr)) {
                $this->appException(new ErrorException($errno, $errstr, $errfile, $errline));
                return;
            }
            // 转换为异常抛出
            throw new ErrorException($errno, $errstr, $errfile, $errline);
        }
    }
    /**
     * 停止处理
     */
    public function appShutdown() {
        if (!is_null($error = error_get_last()) && static::isFatal($error['type'])) {
            // 委托给异常处理
            $this->appException(new ErrorException($error['type'], $error['message'], $error['file'], $error['line']));
        }
    }

    /**
     * 异常处理
     * @param $e
     */
    public function appException($e) {
        $this->handleException($e);
    }

    /**
     * 返回错误级别
     * @param $errno
     * @return string
     */
    public static function levelType($errno) {
        if (static::isError($errno)) {
            return 'error';
        }
        if (static::isWarning($errno)) {
            return 'warning';
        }
        if (static::isNotice($errno)) {
            return 'notice';
        }
        return 'error';
    }

    /**
     * 是否错误类型
     * 全部类型：http://php.net/manual/zh/errorfunc.constants.php
     *
     * @param $errno
     * @return bool
     */
    public static function isError($errno) {
        return in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR]);
    }

    /**
     * 是否警告类型
     * 全部类型：http://php.net/manual/zh/errorfunc.constants.php
     *
     * @param $errno
     * @return bool
     */
    public static function isWarning($errno) {
        return in_array($errno, [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING]);
    }

    /**
     * 是否通知类型
     * 全部类型：http://php.net/manual/zh/errorfunc.constants.php
     *
     * @param $errno
     * @return bool
     */
    public static function isNotice($errno) {
        return in_array($errno, [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_STRICT]);
    }

    /**
     * 是否为致命错误
     * @param $errno
     * @return bool
     */
    public static function isFatal($errno) {
        return in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    /**
     * 是否致命警告类型
     * 特殊的警告，出现后 try/catch 将无法捕获异常。
     * @param $errno
     * @param $errstr
     * @return bool
     */
    public static function isFatalWarning($errno, $errstr) {
        if ($errno == E_WARNING && strpos($errstr, 'require') === 0) {
            return true;
        }
        return false;
    }

    /**
     * 异常处理
     * @param \Throwable $e
     */
    public function handleException(\Throwable $e) {
        // 错误参数定义
        $errors = [
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'type'    => get_class($e),
            'trace'   => $e->getTraceAsString(),
        ];
        // 日志处理
        if ($e instanceof NotFoundException) {
            // 打印到屏幕
            println($errors['message']);
            return;
        }
        // 输出日志
        $this->log($errors);
    }
    /**
     * 输出日志
     * @param array $errors
     */
    protected function log(array $errors) {
        $logger = $this->logger;
        // 构造内容
        list($message, $context) = static::format($errors, \Karthus::$app->appDebug);
        // 写入
        $level = static::levelType($context['code']);
        switch ($level) {
            case 'error':
                $logger->error($message, $context);
                break;
            case 'warning':
                $logger->warning($message, $context);
                break;
            case 'notice':
                $logger->notice($message, $context);
                break;
        }
    }

    /**
     * 格式化
     *
     * @param array $errors
     * @param bool  $debug
     * @return array
     */
    protected static function format(array $errors, bool $debug) {
        $context = $errors;
        $trace   = explode("\n", $context['trace']);
        foreach ($trace as $key => $value) {
            if (strpos($value, '): ') !== false) {
                // 切割为数组
                $fragments   = [];
                $tmp         = explode(' ', $value);
                $fragments[] = array_shift($tmp);
                $tmp1        = explode('): ', join($tmp, ' '));
                $tmp1[0]     .= ')';
                if (count($tmp1) == 2) {
                    // IDE 可识别处理，只有放最后才可识别
                    $fragments[]  = array_pop($tmp1);
                    $fragments[]  = array_pop($tmp1);
                    $fragments[2] = str_replace(['.php(', ')'], ['.php on line ', ''], $fragments[2]);
                    $fragments[2] = 'in ' . $fragments[2];
                    // 合并
                    $value = implode(' ', $fragments);
                }
            }
            $trace[$key] = ' ' . $value;
        }
        $context['trace'] = implode($trace, "\n");
        $message          = "{message}\n[code] {code} [type] {type}\n[file] in {file} on line {line}\n{trace}";
        if (!$debug) {
            $message = "{message} [{code}] {type} in {file} on line {line}";
        }
        return [$message, $context];
    }
}
