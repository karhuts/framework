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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function karthus\app_path;
use function karthus\console_info;

class MakeCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:command')
            ->setDescription('Create a new Artisan command')
            ->addArgument('name', InputArgument::REQUIRED, 'Command name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = $name = trim($input->getArgument('name'));
        // make:command 不支持子目录
        $name = str_replace(['\\', '/'], '', $name);
        $command_str = app_path('command');
        $items = explode(':', $name);
        $name = '';
        foreach ($items as $item) {
            $name .= ucfirst($item);
        }
        $file = "{$command_str}/{$name}.php";
        $upper = $command_str === 'Command';
        $namespace = $upper ? 'App\Command' : 'app\command';
        $this->createCommand($name, $namespace, $file, $command);

        console_info($output, "Console command [{$file}] created successfully.");
        return self::SUCCESS;
    }

    protected function getClassName(string $name): string
    {
        return preg_replace_callback('/:([a-zA-Z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, ucfirst($name)) . 'Command';
    }

    protected function createCommand($name, $namespace, $file, $command): void
    {
        $path = pathinfo($file, PATHINFO_DIRNAME);
        if (! is_dir($path)) {
            @mkdir($path, 0777, true);
        }
        $desc = str_replace(':', ' ', $command);
        $command_content = <<<EOF
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
 
namespace {$namespace};

use Symfony\\Component\\Console\\Command\\Command;
use Symfony\\Component\\Console\\Input\\InputInterface;
use Symfony\\Component\\Console\\Input\\InputOption;
use Symfony\\Component\\Console\\Input\\InputArgument;
use Symfony\\Component\\Console\\Output\\OutputInterface;
use function karthus\\console_info;


class {$name} extends Command
{
    protected static \$defaultName = '{$command}';
    protected static \$defaultDescription = '{$desc}';

    /**
     * @return void
     */
    protected function configure(): void
    {
        \$this->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface \$input
     * @param OutputInterface \$output
     * @return int
     */
    protected function execute(InputInterface \$input, OutputInterface \$output): int
    {
        \$name = \$input->getArgument('name');
        console_info(\$output, 'Hello {$command}');
        return self::SUCCESS;
    }

}
EOF;
        file_put_contents($file, $command_content);
    }
}
