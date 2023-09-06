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

namespace karthus\support;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

use function array_values;
use function is_array;
use function karthus\config;

/**
 * Class Log.
 *
 * @method static void log($level, $message, array $context = [])
 * @method static void debug($message, array $context = [])
 * @method static void info($message, array $context = [])
 * @method static void notice($message, array $context = [])
 * @method static void warning($message, array $context = [])
 * @method static void error($message, array $context = [])
 * @method static void critical($message, array $context = [])
 * @method static void alert($message, array $context = [])
 * @method static void emergency($message, array $context = [])
 */
class Log
{
    protected static array $instance = [];

    /**
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return static::channel()->{$name}(...$arguments);
    }

    /**
     * Channel.
     */
    public static function channel(string $name = 'default'): Logger
    {
        if (! isset(static::$instance[$name])) {
            $config = config('log', [])[$name];
            $handlers = self::handlers($config);
            $processors = self::processors($config);
            static::$instance[$name] = new Logger($name, $handlers, $processors);
        }
        return static::$instance[$name];
    }

    /**
     * Handlers.
     */
    protected static function handlers(array $config): array
    {
        $handlerConfigs = $config['handlers'] ?? [[]];
        $handlers = [];
        foreach ($handlerConfigs as $value) {
            $class = $value['class'] ?? [];
            $constructor = $value['constructor'] ?? [];

            $formatterConfig = $value['formatter'] ?? [];

            $class && $handlers[] = self::handler($class, $constructor, $formatterConfig);
        }

        return $handlers;
    }

    /**
     * Handler.
     */
    protected static function handler(string $class, array $constructor, array $formatterConfig): HandlerInterface
    {
        /** @var HandlerInterface $handler */
        $handler = new $class(...array_values($constructor));

        if ($handler instanceof FormattableHandlerInterface && $formatterConfig) {
            $formatterClass = $formatterConfig['class'];
            $formatterConstructor = $formatterConfig['constructor'];

            /** @var FormatterInterface $formatter */
            $formatter = new $formatterClass(...array_values($formatterConstructor));

            $handler->setFormatter($formatter);
        }

        return $handler;
    }

    /**
     * Processors.
     */
    protected static function processors(array $config): array
    {
        $result = [];
        if (! isset($config['processors']) && isset($config['processor'])) {
            $config['processors'] = [$config['processor']];
        }

        foreach ($config['processors'] ?? [] as $value) {
            if (is_array($value) && isset($value['class'])) {
                $value = new $value['class'](...array_values($value['constructor'] ?? []));
            }
            $result[] = $value;
        }

        return $result;
    }
}
