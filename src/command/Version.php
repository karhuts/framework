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

namespace karthus\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function karthus\base_path;

class Version extends Command
{
    protected static $defaultName = 'version';

    protected static $defaultDescription = 'Show karthus version';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $installed_file = base_path() . '/vendor/composer/installed.php';
        if (is_file($installed_file)) {
            $version_info = include $installed_file;
        }
        $version = $version_info['versions']['karthus/framework']['pretty_version'] ?? '';
        $output->writeln("karthus-framework {$version}");
        return self::SUCCESS;
    }
}
