<?php
declare(strict_types=1);

namespace Karthus\Functions;

class ClearStatCache {
    /**
     * Interval at which to clear fileystem stat cache. Values below 1 indicate
     * the stat cache should ALWAYS be cleared. Otherwise, the value is the number
     * of seconds between clear operations.
     *
     * @var int
     */
    private static $interval = 1;

    /**
     * When the filesystem stat cache was last cleared.
     *
     * @var int
     */
    private static $lastCleared;

    /**
     * @param string|null $filename
     */
    public static function clear(?string $filename = null): void {
        $now = time();
        if (1 > self::$interval
            || self::$lastCleared
            || (self::$lastCleared + self::$interval < $now)
        ) {
            self::forceClear($filename);
            self::$lastCleared = $now;
        }
    }

    /**
     * @param string|null $filename
     */
    public static function forceClear(?string $filename = null): void {
        if ($filename !== null) {
            clearstatcache(true, $filename);
        } else {
            clearstatcache();
        }
    }

    /**
     * @return int
     */
    public static function getInterval(): int {
        return self::$interval;
    }

    /**
     * @param int $interval
     */
    public static function setInterval(int $interval) {
        self::$interval = $interval;
    }
}
