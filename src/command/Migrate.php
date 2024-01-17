<?php
declare(strict_types=1);

namespace karthus\command;

use Illuminate\Database\Schema\Builder;
use karthus\DB;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function karthus\base_path;
use function karthus\runtime_path;

class Migrate extends Command
{

    protected static $defaultName = 'migrate';
    protected static $defaultDescription = 'make migrate';
    protected ?string $connection = null;

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('operate', InputArgument::OPTIONAL, 'make/up/rollback', 'up', ['make', 'up', 'rollback']);
        $this->addArgument('name', InputArgument::OPTIONAL, 'migration name of make');
        $this->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'db connection, [default: "default"]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $operate = strtolower($input->getArgument('operate'));
        $name = $input->getArgument('name');
        $this->connection = $input->getOption('connection');

        if (! in_array($operate, ['make', 'up', 'rollback'])) {
            $output->writeln('<error>operate should is [ make/up/rollback ].</error>');
            return self::INVALID;
        }
        if (strcmp($operate, 'make') === 0 && empty($name)) {
            $output->writeln('<error>name is required.</error>');
            return self::INVALID;
        }

        match ($operate) {
            'make' => $this->make($name),
            'up' => $this->up(),
            'rollback' => $this->rollback(),
        };
        $output->writeln('<info>success!</info>');
        return self::SUCCESS;
    }

    protected function make(string $name): void
    {
        $file = base_path('database/migrations/'.date('Y-m-d-His-').$name.'.php');
        file_put_contents($file, <<<EOF
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

use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;

return new class {

    public function up(Builder \$schema): void
    {
        \$schema->create('', function(Blueprint \$table) {
        });
    }

    public function down(Builder \$schema): void
    {

    }
};
EOF
        );
    }

    protected function rollback(): void
    {
        $migrationLogFile = $this->getMigrationLogFile();
        $rows = $this->fetchMigrationRows($migrationLogFile);
        $latestBatchNum = empty($rows) ? 1 : (int)$rows[count($rows) -1][0];
        $needRollbacks = empty($rows) ? [] : array_values(array_filter($rows, fn($row) => intval($row[0]) === $latestBatchNum));
        $needRollbacks = array_reverse($needRollbacks);

        if (! empty($needRollbacks)) {
            $schema = $this->getSchemaBuilder();
            foreach ($needRollbacks as $needRollback) {
                $obj = require base_path('database/migrations/'.$needRollback[1].'.php');
                $obj->down($schema);
            }

            $logRows = array_filter($rows, fn($row) => intval($row[0]) < $latestBatchNum);
            $content = join(\PHP_EOL, array_map(fn($row) => join(',', $row), $logRows));
            file_put_contents($migrationLogFile, $content);
        }
    }

    protected function up(): void
    {
        $migrationLogFile = $this->getMigrationLogFile();
        $rows = $this->fetchMigrationRows($migrationLogFile);
        $latestBatchNum = empty($rows) ? 0 : (int)$rows[count($rows) -1][0];
        $existMigrations = array_column($rows, 1);
        $needMigrations = [];

        $schema = $this->getSchemaBuilder();

        $dir = new \DirectoryIterator(base_path('database/migrations'));
        $dir->rewind();
        foreach ($dir as $fileInfo) {
            /* @var $fileInfo SplFileInfo */
            if (!$fileInfo->isDot()
                && $fileInfo->isFile()
                && ($basename = $fileInfo->getBasename('.php'))
                && !in_array($basename, $existMigrations)
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
            $appendContent = join(\PHP_EOL,
                array_map(
                    fn($item) => join(',', [
                        $latestBatchNum + 1,
                        $item[1]
                    ]),
                    $needMigrations
                )
            );
            if ($latestBatchNum > 0) {
                $appendContent = \PHP_EOL.$appendContent;
            }
            file_put_contents($migrationLogFile, $appendContent, \FILE_APPEND | \LOCK_EX);
        }
    }

    protected function getSchemaBuilder(): Builder
    {
        return DB::connection($this->connection)->getSchemaBuilder();
    }

    protected function getMigrationLogFile(): string
    {
        return runtime_path('logs/'.($this->connection ?? 'default').'-migrations.log');
    }

    protected function fetchMigrationRows(string $migrationLogFile): array
    {
        if (! file_exists($migrationLogFile)) {
            touch($migrationLogFile);
        }
        $logContent = trim(file_get_contents($migrationLogFile));
        return empty($logContent) ? [] : array_map(fn($row) => explode(',', $row), explode(\PHP_EOL, $logContent));
    }
}
