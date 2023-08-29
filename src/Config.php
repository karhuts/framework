<?php
declare(strict_types=1);
namespace karthus;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Config
{

    /**
     * @var array
     */
    protected static array $config = [];

    /**
     * @var string
     */
    protected static string $configPath = '';

    /**
     * @var bool
     */
    protected static bool $loaded = false;

    /**
     * Load.
     * @param string $configPath
     * @param array $excludeFile
     * @param string|null $key
     * @return void
     */
    public static function load(string $configPath, array $excludeFile = [], string $key = null): void
    {
        static::$configPath = $configPath;
        if (!$configPath) {
            return;
        }
        static::$loaded = false;
        $config = static::loadFromDir($configPath, $excludeFile);
        if (!$config) {
            static::$loaded = true;
            return;
        }
        if ($key !== null) {
            foreach (array_reverse(explode('.', $key)) as $k) {
                $config = [$k => $config];
            }
        }
        static::$config = array_replace_recursive(static::$config, $config);
        static::formatConfig();
        static::$loaded = true;
    }

    /**
     * This deprecated method will certainly be removed in the future.
     * @param string $configPath
     * @param array $excludeFile
     * @return void
     * @deprecated
     */
    public static function reload(string $configPath, array $excludeFile = []): void
    {
        static::load($configPath, $excludeFile);
    }

    /**
     * Clear.
     * @return void
     */
    public static function clear(): void
    {
        static::$config = [];
    }

    /**
     * FormatConfig.
     * @return void
     */
    protected static function formatConfig(): void
    {
        $config = static::$config;
        static::$config = $config;
    }

    /**
     * LoadFromDir.
     * @param string $configPath
     * @param array $excludeFile
     * @return array
     */
    public static function loadFromDir(string $configPath, array $excludeFile = []): array
    {
        $allConfig = [];
        $dirIterator = new RecursiveDirectoryIterator($configPath, FilesystemIterator::FOLLOW_SYMLINKS);
        $iterator = new RecursiveIteratorIterator($dirIterator);
        foreach ($iterator as $file) {
            $filename = $file->getFilename();
            /** var SplFileInfo $file */
            if (is_dir($filename) || $file->getExtension() != 'php' || in_array($file->getBaseName('.php'), $excludeFile)) {
                continue;
            }
            $appConfigFile = $file->getPath() . '/app.php';
            if (!is_file($appConfigFile)) {
                continue;
            }
            $relativePath = str_replace($configPath . DIRECTORY_SEPARATOR, '', substr($filename, 0, -4));
            $explode = array_reverse(explode(DIRECTORY_SEPARATOR, $relativePath));
            if (count($explode) >= 2) {
                $appConfig = include $appConfigFile;
                if (empty($appConfig['enable'])) {
                    continue;
                }
            }
            $config = include $file;
            foreach ($explode as $section) {
                $tmp = [];
                $tmp[$section] = $config;
                $config = $tmp;
            }
            $allConfig = array_replace_recursive($allConfig, $config);
        }
        return $allConfig;
    }

    /**
     * Get.
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed|void|null
     */
    public static function get(string $key = null, mixed $default = null)
    {
        if ($key === null) {
            return static::$config;
        }
        $keyArray = explode('.', $key);
        $value = static::$config;
        $found = true;
        foreach ($keyArray as $index) {
            if (!isset($value[$index])) {
                if (static::$loaded) {
                    return $default;
                }
                $found = false;
                break;
            }
            $value = $value[$index];
        }
        if ($found) {
            return $value;
        }
        return static::read($key, $default);
    }

    /**
     * Read.
     * @param string $key
     * @param mixed|null $default
     * @return array|mixed|null
     */
    protected static function read(string $key, mixed $default = null): mixed
    {
        $path = static::$configPath;
        if ($path === '') {
            return $default;
        }
        $keys = $keyArray = explode('.', $key);
        foreach ($keyArray as $index => $section) {
            unset($keys[$index]);
            if (is_file($file = "$path/$section.php")) {
                $config = include $file;
                return static::find($keys, $config, $default);
            }
            if (!is_dir($path = "$path/$section")) {
                return $default;
            }
        }
        return $default;
    }

    /**
     * Find.
     * @param array $keyArray
     * @param mixed $stack
     * @param mixed $default
     * @return array|mixed
     */
    protected static function find(array $keyArray, mixed $stack, mixed $default): mixed
    {
        if (!is_array($stack)) {
            return $default;
        }
        $value = $stack;
        foreach ($keyArray as $index) {
            if (!isset($value[$index])) {
                return $default;
            }
            $value = $value[$index];
        }
        return $value;
    }

}
