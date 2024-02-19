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

use Doctrine\Inflector\InflectorFactory;
use karthus\DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function karthus\app_path;
use function karthus\classToName;
use function karthus\config;
use function karthus\console_info;
use function karthus\nameToClass;

class MakeModel extends Command
{
    protected static $defaultName = 'make:model';

    protected static $defaultDescription = 'Create a new Eloquent model class';

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Model name');
        $this->addArgument('type', InputArgument::OPTIONAL, 'Type');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $name = nameToClass($name);
        $type = $input->getArgument('type');
        if (! ($pos = strrpos($name, '/'))) {
            $name = ucfirst($name);
            $model_str = app_path('model');
            $file = "{$model_str}/{$name}.php";
            $namespace = $model_str === 'Model' ? 'App\Model' : 'app\model';
        } else {
            $name_str = substr($name, 0, $pos);
            if ($real_name_str = app_path($name_str)) {
                $name_str = $real_name_str;
            } elseif ($real_section_name = app_path(strstr($name_str, '/', true))) {
                $upper = strtolower($real_section_name[0]) !== $real_section_name[0];
            } elseif ($real_base_controller = app_path('controller')) {
                $upper = strtolower($real_base_controller[0]) !== $real_base_controller[0];
            }
            $upper = $upper ?? strtolower($name_str[0]) !== $name_str[0];
            if ($upper && ! $real_name_str) {
                $name_str = preg_replace_callback('/\/([a-z])/', function ($matches) {
                    return '/' . strtoupper($matches[1]);
                }, ucfirst($name_str));
            }
            $path = "{$name_str}/" . ($upper ? 'Model' : 'model');
            $name = ucfirst(substr($name, $pos + 1));
            $file = app_path() . "/{$path}/{$name}.php";
            $namespace = str_replace('/', '\\', ($upper ? 'App/' : 'app/') . $path);
        }
        if (! $type) {
            $database = config('database');
            if (isset($database['default']) && str_starts_with($database['default'], 'plugin.')) {
                $database = false;
            }
            $thinkORM = config('thinkorm');
            if (isset($thinkORM['default']) && str_starts_with($thinkORM['default'], 'plugin.')) {
                $thinkORM = false;
            }
            $type = ! $database && $thinkORM ? 'tp' : 'laravel';
        }
        if ($type == 'tp') {
            $this->createTpModel($name, $namespace, $file);
        } else {
            $this->createModel($name, $namespace, $file);
        }

        console_info($output, "Model [{$file}] created successfully.  ");

        return self::SUCCESS;
    }

    protected function createModel(
        string $class,
        string $namespace,
        string $file
    ): void {
        $path = pathinfo($file, PATHINFO_DIRNAME);
        if (! is_dir($path)) {
            @mkdir($path, 0777, true);
        }
        $table = classToName($class);
        $table_val = 'null';
        $pk = 'id';
        $properties = '';
        try {
            $prefix = config('database.connections.mysql.prefix') ?? '';
            $database = config('database.connections.mysql.database');
            $inflector = InflectorFactory::create()->build();
            $table_plura = $inflector->pluralize($inflector->tableize($class));
            if (DB::select("show tables like '{$prefix}{$table_plura}'")) {
                $table_val = "'{$table}'";
                $table = "{$prefix}{$table_plura}";
            } elseif (DB::select("show tables like '{$prefix}{$table}'")) {
                $table_val = "'{$table}'";
                $table = "{$prefix}{$table}";
            }
            $tableComment = DB::select('SELECT `table_comment` FROM information_schema.`TABLES` WHERE table_schema = ? AND table_name = ?', [$database, $table]);
            if (! empty($tableComment)) {
                $comments = $tableComment[0]->table_comment ?? $tableComment[0]->TABLE_COMMENT;
                $properties .= " * {$table} {$comments}" . PHP_EOL;
            }
            foreach (DB::select("SELECT COLUMN_NAME,DATA_TYPE,COLUMN_KEY,COLUMN_COMMENT FROM INFORMATION_SCHEMA.`COLUMNS` WHERE table_name = '{$table}' AND table_schema = '{$database}'") as $item) {
                if ($item->COLUMN_KEY === 'PRI') {
                    $pk = $item->COLUMN_NAME;
                    $item->COLUMN_COMMENT .= '(主键)';
                }
                $type = $this->getType($item->DATA_TYPE);
                $properties .= " * @property {$type} \${$item->COLUMN_NAME} {$item->COLUMN_COMMENT}\n";
            }
        } catch (Throwable $e) {
        }
        $properties = rtrim($properties) ?: ' *';
        $model_content = <<<EOF
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

use karthus\\Model;

/**
{$properties}
 */
class {$class} extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected \$table = {$table_val};

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected \$primaryKey = '{$pk}';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public \$timestamps = false;
    
    
}

EOF;
        file_put_contents($file, $model_content);
    }

    protected function createTpModel(
        string $class,
        string $namespace,
        string $file
    ): void {
        $path = pathinfo($file, PATHINFO_DIRNAME);
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $table = classToName($class);
        $table_val = 'null';
        $pk = 'id';
        $properties = '';
        try {
            $prefix = config('thinkorm.connections.mysql.prefix') ?? '';
            $database = config('thinkorm.connections.mysql.database');
            if (\think\facade\Db::query("show tables like '{$prefix}{$table}'")) {
                $table = "{$prefix}{$table}";
                $table_val = "'{$table}'";
            } elseif (\think\facade\Db::query("show tables like '{$prefix}{$table}s'")) {
                $table = "{$prefix}{$table}s";
                $table_val = "'{$table}'";
            }
            $tableComment = \think\facade\Db::query('SELECT table_comment FROM information_schema.`TABLES` WHERE table_schema = ? AND table_name = ?', [$database, $table]);
            if (! empty($tableComment)) {
                $comments = $tableComment[0]['table_comment'] ?? $tableComment[0]['TABLE_COMMENT'];
                $properties .= " * {$table} {$comments}" . PHP_EOL;
            }
            foreach (\think\facade\Db::query("select COLUMN_NAME,DATA_TYPE,COLUMN_KEY,COLUMN_COMMENT from INFORMATION_SCHEMA.COLUMNS where table_name = '{$table}' and table_schema = '{$database}'") as $item) {
                if ($item['COLUMN_KEY'] === 'PRI') {
                    $pk = $item['COLUMN_NAME'];
                    $item['COLUMN_COMMENT'] .= '(主键)';
                }
                $type = $this->getType($item['DATA_TYPE']);
                $properties .= " * @property {$type} \${$item['COLUMN_NAME']} {$item['COLUMN_COMMENT']}\n";
            }
        } catch (Throwable $e) {
        }
        $properties = rtrim($properties) ?: ' *';
        $model_content = <<<EOF
<?php

namespace {$namespace};

use think\\Model;

/**
{$properties}
 */
class {$class} extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected \$table = {$table_val};

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected \$pk = '{$pk}';

    
}

EOF;
        file_put_contents($file, $model_content);
    }

    protected function getType(string $type): string
    {
        if (str_contains($type, 'int')) {
            return 'int';
        }
        return match ($type) {
            'varchar', 'string', 'text', 'date', 'time', 'guid', 'datetimetz', 'datetime', 'decimal', 'enum', 'json', 'char' => 'string',
            'boolean' => 'int',
            'float', 'double' => 'float',
            default => 'mixed',
        };
    }
}
