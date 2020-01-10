<?php
declare(strict_types=1);
namespace Karthus\Coroutine;

use Karthus\Traits\Container;
use Swoole\Coroutine as SwooleCoroutine;


class Locker {
    use Container;
    /**
     * @var array
     */
    protected static $container = [];

    /**
     * @param $key
     * @param $id
     */
    public static function add($key, $id): void {
        self::$container[$key][] = $id;
    }

    /**
     * @param $key
     */
    public static function clear($key): void {
        unset(self::$container[$key]);
    }

    /**
     * @param $key
     * @return bool
     */
    public static function lock($key): bool {
        if (! self::has($key)) {
            self::add($key, 0);
            return true;
        }
        self::add($key, Coroutine::id());
        SwooleCoroutine::suspend();
        return false;
    }

    /**
     * @param $key
     */
    public static function unlock($key): void {
        if (self::has($key)) {
            $ids = self::get($key);
            foreach ($ids as $id) {
                if ($id > 0) {
                    SwooleCoroutine::resume($id);
                }
            }
            self::clear($key);
        }
    }
}
