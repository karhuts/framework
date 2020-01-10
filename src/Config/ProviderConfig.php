<?php
declare(strict_types=1);

namespace Karthus\Config;

use Karthus\Functions\Composer;
use function class_exists;
use function is_string;
use function method_exists;
/**
 * Provider config allow the components set the configs to application.
 */
class ProviderConfig {
    /**
     * @var array
     */
    private static $providerConfigs = [];
    /**
     * Load and merge all provider configs from components.
     * Notice that this method will cached the config result into a static property,
     * call ProviderConfig::clear() method if you want to reset the static property.
     */
    public static function load(): array {
        if (! static::$providerConfigs) {
            $providers = Composer::getMergedExtra('karthus')['config'] ?? [];
            static::$providerConfigs = static::loadProviders($providers);
        }
        return static::$providerConfigs;
    }

    public static function clear(): void {
        static::$providerConfigs = [];
    }

    /**
     * @param array $providers
     * @return array
     */
    protected static function loadProviders(array $providers): array {
        $providerConfigs = [];
        foreach ($providers as $provider) {
            if (is_string($provider) && class_exists($provider) && method_exists($provider, '__invoke')) {
                $providerConfigs[] = (new $provider())();
            }
        }
        return static::merge(...$providerConfigs);
    }

    /**
     * @param mixed ...$arrays
     * @return array
     */
    protected static function merge(...$arrays): array {
        $result = array_merge_recursive(...$arrays);
        if (isset($result['dependencies'])) {
            $dependencies = array_column($arrays, 'dependencies');
            $result['dependencies'] = array_merge(...$dependencies);
        }
        return $result;
    }
}
