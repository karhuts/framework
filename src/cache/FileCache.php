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

namespace karthus\cache;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class FileCache implements CacheInterface
{
    protected string $cacheFilePath;

    protected int $ttl;

    public function __construct(string $cacheFilePath, int $ttl = 86400)
    {
        $this->cacheFilePath = $cacheFilePath;
        $this->ttl = $ttl;
    }

    public function get($key, $default = null): mixed
    {
        return ($this->has($key)) ? file_get_contents($this->cacheFilePath) : $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        return (bool) file_put_contents($this->cacheFilePath, $value);
    }

    public function has($key): bool
    {
        return file_exists($this->cacheFilePath) && time() - filemtime($this->cacheFilePath) < $this->ttl;
    }

    public function delete($key): bool
    {
        return unlink($this->cacheFilePath);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function clear(): bool
    {
        return $this->delete($this->cacheFilePath);
    }

    public function getMultiple($keys, $default = null): iterable
    {
        return [];
    }

    public function setMultiple($values, $ttl = null): bool
    {
        return false;
    }

    public function deleteMultiple($keys): bool
    {
        return false;
    }
}
