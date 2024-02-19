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
use function karthus\config;
use function karthus\console_info;
use function karthus\guessPath;

class MakeController extends Command
{
    protected function configure(): void
    {
        $this->setName('make:controller')
            ->setDescription('Create a new controller class')
            ->addArgument('name', InputArgument::REQUIRED, 'Controller name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $suffix = config('app.controller_suffix', '');
        if ($suffix && ! strpos($name, $suffix)) {
            $name .= $suffix;
        }

        $name = str_replace('\\', '/', $name);
        if (! ($pos = strrpos($name, '/'))) {
            $name = ucfirst($name);
            $controller_str = app_path('controller');
            $file = "{$controller_str}/{$name}.php";
            $namespace = $controller_str === 'Controller' ? 'App\Controller' : 'app\controller';
        } else {
            $name_str = substr($name, 0, $pos);
            if ($real_name_str = guessPath(app_path(), $name_str)) {
                $name_str = $real_name_str;
            }

            $name_str = ucfirst($name_str);
            $name = ucfirst(substr($name, $pos + 1));
            $file = app_path('controller') . "/{$name_str}/{$name}.php";
            $namespace = str_replace('/', '\\', "app/controller/{$name_str}");
        }
        $this->createController($name, $namespace, $file);

        console_info($output, "Controller [{$file}] created successfully.");
        return self::SUCCESS;
    }

    protected function createController(string $name, string $namespace, string $file): void
    {
        $path = pathinfo($file, PATHINFO_DIRNAME);
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $controller_content = <<<EOF
<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  min@bluecity.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace {$namespace};

use Laminas\\Diactoros\\Response\\HtmlResponse;
use Psr\\Http\\Message\\ResponseInterface;
use Psr\\Http\\Message\\ServerRequestInterface;
use function karthus\\http_responses_message;
use function karthus\\view;

class {$name}
{
    public function index(ServerRequestInterface \$request): HtmlResponse
    {
        return view('index');
    }
    
    public function data(ServerRequestInterface \$request): array
    {
        return http_responses_message();
    }

}
EOF;
        file_put_contents($file, $controller_content);
    }
}
