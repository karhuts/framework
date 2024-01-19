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

namespace karthus\route\Cache;

use karthus\route\Router as MainRouter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

use function Opis\Closure\serialize;
use function Opis\Closure\unserialize;

class Router
{
    public const CACHE_KEY = 'karthus/route/cache';

    /**
     * @var callable
     */
    protected static $builder;

    protected static CacheInterface $cache;

    protected static int $ttl = 86400;

    protected static bool $cacheEnabled = false;

    public static function withCache(CacheInterface $cache): void
    {
        self::$cache = $cache;
    }

    public static function withCacheTTL(int $ttl = 86400): void
    {
        self::$ttl = $ttl;
    }

    public static function withBuilder(callable $builder): void
    {
        self::$builder = $builder;
    }

    public static function withCacheEnable(bool $enable = false): void
    {
        self::$cacheEnabled = (bool) $enable;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function dispatch(ServerRequestInterface $request): void
    {
        $cache = self::$cache->get(static::CACHE_KEY);
        if (self::$cacheEnabled === true && $cache) {
            @unserialize($cache, ['allowed_classes' => true]);
        } else {
            call_user_func(self::$builder, MainRouter::class);
            $router = MainRouter::getRoutes();
            self::$cache->set(static::CACHE_KEY, @serialize($router));
        }
    }
}
