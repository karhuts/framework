<?php
declare(strict_types=1);
namespace Karthus\Coroutine;

use Swoole\Coroutine as SwooleCoroutine;

class Coroutine {
    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments) {
        if (! method_exists(SwooleCoroutine::class, $name)) {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s.', $name));
        }
        return SwooleCoroutine::$name(...$arguments);
    }

    /**
     * Returns the current coroutine ID.
     * Returns -1 when running in non-coroutine context.
     */
    public static function id(): int {
        return SwooleCoroutine::getCid();
    }

    /**
     * 创建协程
     * @param callable $callback
     * @param mixed ...$params
     * @return int
     */
    public static function create(callable $callback, ...$params) {
        $result =  SwooleCoroutine::create(function () use ($callback, $params) {
            try {
                // 执行闭包
                call_user_func_array($callback, $params);
            } catch (\Throwable $throwable) {
                // 错误处理
                if (!class_exists(\Karthus::class)) {
                    throw $throwable;
                }
                // 错误处理
                /** @var \Karthus\Console\Error $error */
                $error = \Karthus::$app->context->get('error');
                $error->handleException($throwable);
            }
        });
        return is_int($result) ? $result : -1;
    }


    /**
     * 延迟执行
     * @param callable $callback
     * @return void
     */
    public static function defer(callable $callback) {
        SwooleCoroutine::defer(function () use ($callback) {
            try {
                // 执行闭包
                call_user_func($callback);
            } catch (\Throwable $throwable) {
                if (!class_exists(\Karthus::class)) {
                    throw $throwable;
                }
                // 错误处理
                /** @var \Karthus\Console\Error $error */
                $error = \Karthus::$app->context->get('error');
                $error->handleException($throwable);
            }
        });
    }

    /**
     * @return bool
     */
    public static function inCoroutine(): bool {
        return Coroutine::id() > 0;
    }

    /**
     * Returns the parent coroutine ID.
     * Returns -1 when running in the top level coroutine.
     * Returns null when running in non-coroutine context.
     *
     * @see https://github.com/swoole/swoole-src/pull/2669/files#diff-3bdf726b0ac53be7e274b60d59e6ec80R940
     */
    public static function parentId(): ?int {
        $cid = SwooleCoroutine::getPcid();
        if ($cid === false) {
            return null;
        }
        return $cid;
    }
}
