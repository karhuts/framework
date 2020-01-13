<?php
declare(strict_types=1);

namespace Karthus\Config;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Symfony\Component\Finder\Finder;
class ConfigFactory {

    /**
     * @param ContainerInterface $container
     * @return Config
     */
    public function __invoke(ContainerInterface $container) {
        // Load env before config.
        if (file_exists(BASE_PATH . '/.env')) {
            Dotenv::create([BASE_PATH])->load();
        }
        $configPath = BASE_PATH . '/config/';
        $config = $this->readConfig($configPath . 'config.php');
        $serverConfig = $this->readConfig($configPath . 'server.php');
        $autoloadConfig = $this->readPaths([BASE_PATH . '/config/autoload']);
        $merged = array_merge_recursive(ProviderConfig::load(), $serverConfig, $config, ...$autoloadConfig);
        return new Config($merged);
    }

    /**
     * 读取配置
     *
     * @param string $configPath
     * @return array
     */
    private function readConfig(string $configPath): array {
        $config = [];
        if (file_exists($configPath) && is_readable($configPath)) {
            $config = require $configPath;
        }
        return is_array($config) ? $config : [];
    }

    /**
     * 读取路径
     *
     * @param array $paths
     * @return array
     */
    private function readPaths(array $paths) {
        $configs = [];
        $finder = new Finder();
        $finder->files()->in($paths)->name('*.php');
        foreach ($finder as $file) {
            $configs[] = [
                $file->getBasename('.php') => require $file->getRealPath(),
            ];
        }
        return $configs;
    }
}
