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

use Illuminate\Database\Schema\Builder;
use karthus\DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function karthus\base_path;
use function karthus\runtime_path;

use const PHP_EOL;

class MigrateRollback extends Command
{
    protected static $defaultName = 'migrate:rollback';

    protected static $defaultDescription = 'Rollback the last database migration';

    protected ?string $connection = null;

    protected function configure(): void
    {
        $this->addOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use, [default: "default"]');
        $this->addOption('force', null, InputOption::VALUE_OPTIONAL, 'Force the operation to run when in production');
        $this->addOption('step', null, InputOption::VALUE_OPTIONAL, 'Force the migrations to be run so they can be rolled back individually');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection = $input->getOption('database');
        $migrationLogFile = $this->getMigrationLogFile();
        $rows = $this->fetchMigrationRows($migrationLogFile);
        $latestBatchNum = empty($rows) ? 1 : (int) $rows[count($rows) - 1][0];
        $needRollbacks = empty($rows) ? [] : array_values(array_filter($rows, fn ($row) => intval($row[0]) === $latestBatchNum));
        $needRollbacks = array_reverse($needRollbacks);

        if (! empty($needRollbacks)) {
            $schema = $this->getSchemaBuilder();
            foreach ($needRollbacks as $needRollback) {
                $obj = require base_path('database/migrations/' . $needRollback[1] . '.php');
                $obj->down($schema);
            }

            $logRows = array_filter($rows, fn ($row) => intval($row[0]) < $latestBatchNum);
            $content = join(PHP_EOL, array_map(fn ($row) => join(',', $row), $logRows));
            file_put_contents($migrationLogFile, $content);
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
