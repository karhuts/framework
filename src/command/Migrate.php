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

use DirectoryIterator;
use Illuminate\Database\Schema\Builder;
use karthus\DB;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function karthus\base_path;
use function karthus\runtime_path;

use const FILE_APPEND;
use const LOCK_EX;
use const PHP_EOL;

class Migrate extends Command
{
    protected ?string $connection = null;

    protected function configure(): void
    {
        $this->setName('migrate')
            ->setDescription('Run the database migrations')
            ->addOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use, [default: "default"]')
            ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'Force the operation to run when in production')
            ->addOption('step', null, InputOption::VALUE_OPTIONAL, 'Force the migrations to be run so they can be rolled back individually');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection = $input->getOption('database');
        $migrationLogFile = $this->getMigrationLogFile();
        $rows = $this->fetchMigrationRows($migrationLogFile);
        $latestBatchNum = empty($rows) ? 0 : (int) $rows[count($rows) - 1][0];
        $existMigrations = array_column($rows, 1);
        $needMigrations = [];

        $schema = $this->getSchemaBuilder();
        $dir = new DirectoryIterator(base_path('database/migrations'));
        $dir->rewind();
        foreach ($dir as $fileInfo) {
            /* @var $fileInfo SplFileInfo */
            if (! $fileInfo->isDot()
                && $fileInfo->isFile()
                && ($basename = $fileInfo->getBasename('.php'))
                && ! in_array($basename, $existMigrations)
            ) {
                $needMigrations[] = [$fileInfo->getRealPath(), $basename];
            }
        }

        if (! empty($needMigrations)) {
            sort($needMigrations);
            foreach ($needMigrations as $file) {
                $obj = require $file[0];
                $obj->up($schema);
            }
            $appendContent = join(
                PHP_EOL,
                array_map(
                    fn ($item) => join(',', [
                        $latestBatchNum + 1,
                        $item[1],
                    ]),
                    $needMigrations
                )
            );
            if ($latestBatchNum > 0) {
                $appendContent = PHP_EOL . $appendContent;
            }
            file_put_contents($migrationLogFile, $appendContent, FILE_APPEND | LOCK_EX);
        }
        $output->writeln('<info>success!</info>');
        return self::SUCCESS;
    }

    protected function getSchemaBuilder(): Builder
    {
        return DB::connection($this->connection)->getSchemaBuilder();
    }

    protected function getMigrationLogFile(): string
    {
        return runtime_path('logs/' . ($this->connection ?? 'default') . '-migrations.log');
    }

    protected function fetchMigrationRows(string $migrationLogFile): array
    {
        if (! file_exists($migrationLogFile)) {
            touch($migrationLogFile);
        }
        $logContent = trim(file_get_contents($migrationLogFile));
        return empty($logContent) ? [] : array_map(fn ($row) => explode(',', $row), explode(PHP_EOL, $logContent));
    }
}
