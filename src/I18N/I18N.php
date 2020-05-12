<?php
declare(strict_types=1);

namespace Karthus\I18N;

use Karthus\Component\Singleton;
use Karthus\Config;
use Swoole\Coroutine;

/**
 * 国际化，语言包
 *
 * Class I18N
 *
 * @package Karthus\I18N
 */
class I18N {
    use Singleton;

    protected $context = [];
    protected $default = null;
    protected $parsed  = [];
    private   $fallback;
    private   $path;
    private $loaded = [];


    /**
     * I18N constructor.
     */
    public function __construct() {
        $I18N           = Config::getInstance()->getConf('I18N');
        $this->fallback = $I18N['fallback_locale'] ?? 'en-us';
        $this->default  = $I18N['locale'] ?? 'zh-CN';
        $this->path     = $I18N['path'] ?? KARTHUS_ROOT . '/languages';
    }

    /**
     * 获取协程ID
     *
     * @return int
     */
    private function cid():int {
        $cid = Coroutine::getCid();
        if(!isset($this->context[$cid]) && $cid > 0){
            Coroutine::defer(function ()use($cid){
                unset($this->context[$cid]);
            });
        }
        return $cid;
    }

    /**
     * 翻译
     *
     * @param string      $key 健名
     * @param array       $replace 替代值
     * @param string|null $locale 语言包名
     * @param bool        $fallback 是否可以回滚
     * @return mixed
     */
    public function translate(string $key,
                              array $replace = [],
                              ?string $locale = null,
                              bool $fallback = true){
        [$group, $item] = $this->parseKey($key);
        $locales    = $fallback ? $this->localeArray($locale) : [$locale ?: $this->locale()];

        foreach ($locales as $locale) {
            $line = $this->getTranslate($group, $locale, $item, $replace);
            if (!is_null($line)) {
                break;
            }
        }

        return $line ?? $key;
    }

    /**
     * @param string $namespace
     * @param string $group
     * @param string $locale
     * @param        $item
     * @param array  $replace
     */
    private function getTranslate(string $group, string $locale, $item, array $replace) {
        $this->load($group, $locale);
        if (!is_null($item)) {
            $data = $this->loaded[$group][$locale][$item] ?? null;
        } else {
            $data = $this->loaded[$group][$locale];
        }

        if (is_string($data)) {
            return vsprintf($data, $replace);
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $line[$key] = vsprintf($value, $replace);
            }
            return $line;
        }

        return '';
    }

    /**
     * @return string
     */
    public function locale(): string {
        $locale = $this->context[$this->cid()];
        return (string) ($locale ?? $this->default);
    }

    /**
     * 语言包转换为数组
     *
     * @param string|null $locale
     * @return array
     */
    protected function localeArray(?string $locale): array {
        return array_filter([$locale ?: $this->locale(), $this->fallback]);
    }

    /**
     * Parse a key into namespace, group, and item.
     *
     * @param string $key
     * @return array
     */
    private function parseKey(string $key): array {
        if (isset($this->parsed[$key])) {
            return $this->parsed[$key];
        }

        $parsed = $this->parseNamespacedSegments($key);
        if (is_null($parsed[0])) {
            $parsed[0] = '*';
        }

        return $this->parsed[$key] = $parsed;
    }

    /**
     * Parse an array of basic segments.
     *
     * @param array $segments
     * @return array
     */
    protected function parseBasicSegments(array $segments): array {
        $group  = $segments[0];
        $item   = count($segments) === 1 ? null : implode('.', array_slice($segments, 1));
        return [$group, $item];
    }

    /**
     * Parse an array of namespaced segments.
     *
     * @param string $key
     * @return array
     */
    protected function parseNamespacedSegments(string $key): array {
        $itemSegments       = explode('.', $key);
        $groupAndItem       = $this->parseBasicSegments($itemSegments);
        return $groupAndItem;
    }

    /**
     * @param string $group
     * @param string $locale
     */
    public function load(string $group, string $locale) {
        if ($this->isLoaded($group, $locale)) {
            return;
        }
        $data   = $this->loadFiles($locale, $group);
        $this->loaded[$group][$locale] = $data;
    }

    /**
     * 判断是否已经加载
     *
     * @param string $group
     * @param string $locale
     * @return bool
     */
    protected function isLoaded(string $group, string $locale): bool {
        return isset($this->loaded[$group][$locale]);
    }

    /**
     * 加载文件
     *
     * @param string      $locale
     * @param string      $group
     * @return array
     */
    public function loadFiles(string $locale, string $group): array {
        $filename   = "{$this->path}/{$locale}/{$group}.php";
        if (file_exists($filename)) {
            return require_once($filename);
        }
        return [];
    }
}
