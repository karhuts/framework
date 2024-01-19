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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function karthus\base_path;
use function karthus\console_error;
use function karthus\console_info;

class MakeMigration extends Command
{
    protected static $defaultName = 'make:migration';

    protected static $defaultDescription = 'Create a new migration file';

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the migration');
        $this->addOption('create', 'CREATE', InputOption::VALUE_OPTIONAL, ' The table to be created');
        $this->addOption('table', 'TABLE', InputOption::VALUE_OPTIONAL, 'The table to migrate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        if (empty($name)) {
            console_error($output, 'name is required.');
            return self::INVALID;
        }

        $table = $input->getOption('table');
        $table = $table === null ? '' : $table;
        $create = $input->getOption('create');
        $create = $create === null ? '' : $create;

        $file = base_path('database/migrations/' . date('Y_m_d_His_') . $name . '.php');
        if ($create) {
            $up = <<<EOT
DB::schema()->create('{$create}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
EOT;

            $down = <<<EOT
DB::schema()->dropIfExists('{$create}');
EOT;
        } else {
            $up = <<<EOT
DB::schema()->table('{$table}', function (Blueprint \$table) {
            \$table->string('new_column')->nullable(false);
        });
EOT;
            $down = <<<EOT
DB::schema()->table('{$table}', function (Blueprint \$table) {
            \$table->dropColumn('new_column');
        });
EOT;
        }
        file_put_contents(
            $file,
            <<<EOF
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
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use karthus\\DB;

return new class() extends Migration {
    public function up(): void
    {
        {$up}
    }

    public function down(): void
    {
        {$down}
    }
};
EOF
        );

        console_info($output, "Migration [{$file}] created successfully.");
        return self::SUCCESS;
    }
}
