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

namespace karthus;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command as sCommand;

class Command extends Application
{
    public function installInternalCommands(): self
    {
        $this->installCommands(
            __DIR__ . '/Command',
            'karthus\command'
        );

        return $this;
    }

    public function installCommands($path, string $namespace = 'app\command'): self
    {
        // 如果path 不存在
        if (! is_dir($path)) {
            return $this;
        }

        $dir_iterator = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $relativePath = str_replace(str_replace('/', '\\', $path . '\\'), '', str_replace('/', '\\', $file->getRealPath()));
            $realNamespace = trim($namespace . '\\' . trim(dirname(str_replace('\\', DIRECTORY_SEPARATOR, $relativePath)), '.'), '\\');
            $realNamespace = str_replace('/', '\\', $realNamespace);
            $class_name = trim($realNamespace . '\\' . $file->getBasename('.php'), '\\');
            if (! class_exists($class_name) || ! is_a($class_name, sCommand::class, true) || (new ReflectionClass($class_name))->isAbstract()) {
                continue;
            }
            $this->add(new $class_name());
        }

        return $this;
    }
}
