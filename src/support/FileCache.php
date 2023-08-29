<?php
declare(strict_types=1);
namespace karthus\support;

use JsonException;
use karthus\Singleton;
use stdClass;

class FileCache {
    use Singleton;

    private string $cache_path; //缓存路径

    /**
     * @param string $path
     */
    public function __construct(string $path = "cache") {
        $this->cache_path = runtime_path($path);
    }

    /**
     * 文件缓存-设置缓存
     * 设置缓存名称，数据，和缓存时间
     * @param string $key
     * @param array|string|bool|stdClass $data 缓存数据
     * @param int $time
     * @return bool
     * @throws JsonException
     */
    public function set(string $key, array|string|bool|stdClass $data, int $time = 0): bool {
        $filename = $this->get_cache_filename($key);
        $data = [
            'T' => microtime(true),
            'ttl' => $time,
            'D' => $data,
            'K' => $key,
        ];
        $payload = \msgpack_pack($data);
        @file_put_contents($filename, $payload, LOCK_EX);
        clearstatcache();
        return true;
    }

    /**
     * 文件缓存-获取缓存
     * 获取缓存文件，分离出缓存开始时间和缓存时间
     * 返回分离后的缓存数据，解序列化
     * @param string $key 缓存名
     * @return bool|array|stdClass|string
     */
    public function get(string $key): bool|array|stdClass|string {
        $filename = $this->get_cache_filename($key);
        /* 缓存不存在的情况 */
        if (!file_exists($filename)) {
            return false;
        }
        $data = file_get_contents($filename); //获取缓存
        if ($data === "") {
            @unlink($filename);
            return false;
        }
        $payload = \msgpack_unpack($data);
        if (empty($payload)) {
            @unlink($filename);
            return false;
        }

        $timestamp = microtime(true);
        $ttl = $payload['ttl'] ?? 0;
        $T = $payload['T'] ?? 0;
        $content = $payload['D'] ?? "";
        // 缓存永不过期
        if ($ttl === -1) {
            return $content;
        }

        if ($ttl === 0) {
            @unlink($filename);
            return false;
        }

        //缓存过期
        if (($T + $ttl) < $timestamp) {
            @unlink($filename);
            return false;
        }

        return $content;
    }

    /**
     * 文件缓存-清除缓存
     * 删除缓存文件
     * @param string $filename 缓存名
     * @return bool
     */
    public function clear(string $filename): bool {
        $filename = $this->get_cache_filename($filename);
        if (!file_exists($filename)) {
            return true;
        }
        @unlink($filename);
        return true;
    }

    /**
     * 文件缓存-清除全部缓存
     * 删除整个缓存文件夹文件，一般情况下不建议使用
     * @return bool
     */
    public function clear_all(): bool {
        @set_time_limit(3600);
        $path = opendir($this->cache_path);
        while (false !== ($filename = readdir($path))) {
            if ($filename !== '.' && $filename !== '..') {
                @unlink($this->cache_path . '/' .$filename);
            }
        }
        closedir($path);
        return true;
    }

    /**
     * 设置文件缓存路径
     * @param string $path 路径
     * @return string
     */
    public function set_cache_path(string $path): string {
        return $this->cache_path = $path;
    }

    /**
     * 获取缓存文件名
     * @param string $filename 缓存名
     * @return string
     */
    private function get_cache_filename(string $filename): string {
        $filename = md5($filename); //文件名MD5加密
        return $this->cache_path .'/'. $filename . '.data';
    }
}
