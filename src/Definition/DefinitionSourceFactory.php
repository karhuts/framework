<?php
declare(strict_types=1);
namespace Karthus\Definition;

use Karthus\Config\ProviderConfig;
use Karthus\Exception\Exception;

class DefinitionSourceFactory {
    /**
     * @var bool
     */
    protected $enableCache = false;
    /**
     * @var string
     */
    protected $baseUri;

    /***
     * DefinitionSourceFactory constructor.
     *
     * @param bool $enableCache
     * @throws Exception
     */
    public function __construct(bool $enableCache = false) {
        $this->enableCache = $enableCache;
        if (! defined('BASE_PATH')) {
            throw new Exception('BASE_PATH is not defined.');
        }
        $this->baseUri = BASE_PATH;
    }

    /**
     * @return DefinitionSource
     */
    public function __invoke() {
        $configDir = $this->baseUri . '/config';
        $configFromProviders = [];
        if (class_exists(ProviderConfig::class)) {
            $configFromProviders = ProviderConfig::load();
        }
        $serverDependencies = $configFromProviders['dependencies'] ?? [];
        if (file_exists($configDir . '/autoload/dependencies.php')) {
            $definitions = include $configDir . '/autoload/dependencies.php';
            $serverDependencies = array_replace($serverDependencies, $definitions ?? []);
        }
        $scanDirs = $configFromProviders['annotations']['scan']['paths'] ?? [];
        $ignoreAnnotations = $configFromProviders['annotations']['scan']['ignore_annotations'] ?? [];
        $collectors = $configFromProviders['annotations']['scan']['collectors'] ?? [];
        // Load the config/autoload/annotations.php and merge the config
        if (file_exists($configDir . '/autoload/annotations.php')) {
            $annotations = include $configDir . '/autoload/annotations.php';
            $scanDirs = array_merge($scanDirs, $annotations['scan']['paths'] ?? []);
            $ignoreAnnotations = array_merge($ignoreAnnotations, $annotations['scan']['ignore_annotations'] ?? []);
            $collectors = array_merge($collectors, $annotations['scan']['collectors'] ?? []);
        }
        // Load the config/config.php and merge the config
        if (file_exists($configDir . '/config.php')) {
            $configContent = include $configDir . '/config.php';
            if (isset($configContent['annotations'])) {
                $scanDirs = array_merge($scanDirs, $configContent['annotations']['scan']['paths'] ?? []);
                $ignoreAnnotations = array_merge($ignoreAnnotations, $configContent['annotations']['scan']['ignore_annotations'] ?? []);
                $collectors = array_merge($collectors, $configContent['annotations']['scan']['collectors'] ?? []);
            }
        }
        $scanConfig = new ScanConfig($scanDirs, $ignoreAnnotations, $collectors);
        return new DefinitionSource($serverDependencies, $scanConfig, $this->enableCache);
    }
}
