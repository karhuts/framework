<?php
declare(strict_types=1);
namespace Karthus\Console;

use Karthus\Console\CommandLine\Argument;
use Karthus\Console\CommandLine\Flag;
use Karthus\Console\Event\CommandBeforeExecuteEvent;
use Karthus\Exception\NotFoundException;
use Karthus\Injector\ApplicationContext;
use Karthus\Injector\BeanInjector;
use PhpDocReader\AnnotationException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Scheduler;

/**
 * Class Application
 *
 * @package Karthus\Console
 */
class Application {
    /**
     * 应用名称
     * @var string
     */
    public $appName = 'app-console';
    /**
     * 应用版本
     * @var string
     */
    public $appVersion = '0.0.0';
    /**
     * 应用调试
     * @var bool
     */
    public $appDebug = true;
    /**
     * 基础路径
     * @var string
     */
    public $basePath = '';

    /**
     * 协程
     * @var array
     */
    public $coroutine = [
        true, [
            'max_coroutine' => 300000,
            'hook_flags'    => 1879048191, // SWOOLE_HOOK_ALL
        ],
    ];
    /**
     * Context
     * @var ApplicationContext
     */
    public $context;
    /**
     * 命令
     * @var array
     */
    public $commands = [];
    /**
     * 依赖配置
     * @var array
     */
    public $beans = [];
    /**
     * 是否为单命令
     * @var bool
     */
    protected $isSingleCommand = false;
    /**
     * Error
     * @var Error
     */
    protected $error;
    /**
     * EventDispatcher
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Application constructor.
     * @param array $config
     */
    public function __construct(array $config) {
        // 注入
        try {
            BeanInjector::inject($this, $config);
        } catch (AnnotationException $e) {
        } catch (\ReflectionException $e) {
        }
        // 保存引用
        \Karthus::$app         = $this;
        // 初始化上下文
        $this->context         = new ApplicationContext($this->beans);
        // 加载核心库
        $this->error           = $this->context->get('error');
        $this->eventDispatcher = $this->context->get('event');
        // 是否为单命令
        $commands              = $this->commands;
        $frist                 = array_shift($commands);
        $this->isSingleCommand = is_string($frist);
    }

    /**
     * 执行功能 (CLI模式)
     */
    public function run() {
        if (PHP_SAPI != 'cli') {
            throw new \RuntimeException('please run in cli mode.');
        }
        Flag::init();
        if (Argument::command() == '') {
            if (Flag::bool(['h', 'help'], false)) {
                $this->help();
                return;
            }
            if (Flag::bool(['v', 'version'], false)) {
                $this->version();
                return;
            }
            $options = Flag::options();
            if (empty($options)) {
                $this->help();
                return;
            } elseif ($this->isSingleCommand) {
                // 单命令执行
                $this->callCommand(Argument::command());
                return;
            }
            $keys   = array_keys($options);
            $flag   = array_shift($keys);
            $script = Argument::script();
            throw new NotFoundException("flag provided but not defined: '{$flag}', see '{$script} --help'."); // 这里只是全局flag效验
        }
        if (Argument::command() !== '' && Flag::bool('help', false)) {
            $this->commandHelp();
            return;
        }
        // 非单命令执行
        $this->callCommand(Argument::command());
    }
    /**
     * 帮助
     */
    protected function help() {
        $script = Argument::script();
        println("Usage: {$script}" . ($this->isSingleCommand ? '' : ' [OPTIONS] COMMAND') . " [opt...]");
        $this->printOptions();
        if (!$this->isSingleCommand) {
            $this->printCommands();
        } else {
            $this->printCommandOptions();
        }
        println('');
        println("Run '{$script}" . ($this->isSingleCommand ? '' : ' COMMAND') . " --help' for more information on a command.");
        println('');
        println("Developed with Karthus PHP framework. (https://git.blued.cn/min/karthus)");
    }
    /**
     * 命令帮助
     */
    protected function commandHelp() {
        $script  = Argument::script();
        $command = Argument::command();
        println("Usage: {$script} {$command} [opt...]");
        $this->printCommandOptions();
        println('');
        println("Developed with Karthus PHP framework. (https://git.blued.cn/min/karthus)");
    }
    /**
     * 版本
     */
    protected function version() {
        $appName          = \Karthus::$app->appName;
        $appVersion       = \Karthus::$app->appVersion;
        $frameworkVersion = \Karthus::$version;
        println("{$appName} version {$appVersion}, framework version {$frameworkVersion}");
    }
    /**
     * 打印选项列表
     */
    protected function printOptions() {
        $tabs = "\t";
        println('');
        println('Global Options:');
        println("  -h, --help{$tabs}Print usage");
        println("  -v, --version{$tabs}Print version information");
    }
    /**
     * 打印命令列表
     */
    protected function printCommands() {
        println('');
        println('Commands:');
        foreach ($this->commands as $key => $item) {
            $command     = $key;
            $subCommand  = '';
            $description = $item['description'] ?? '';
            if (strpos($key, ' ') !== false) {
                list($command, $subCommand) = explode(' ', $key);
            }
            if ($subCommand == '') {
                println("  {$command}\t{$description}");
            } else {
                println("  {$command} {$subCommand}\t{$description}");
            }
        }
    }
    /**
     * 打印命令选项列表
     */
    protected function printCommandOptions() {
        $command = Argument::command();
        $options = [];
        if (!$this->isSingleCommand) {
            if (isset($this->commands[$command]['options'])) {
                $options = $this->commands[$command]['options'];
            }
        } else {
            if (isset($this->commands['options'])) {
                $options = $this->commands['options'];
            }
        }
        if (empty($options)) {
            return;
        }
        println('');
        println('Command Options:');
        foreach ($options as $option) {
            $names = array_shift($option);
            if (is_string($names)) {
                $names = [$names];
            }
            $flags = [];
            foreach ($names as $name) {
                if (strlen($name) == 1) {
                    $flags[] = "-{$name}";
                } else {
                    $flags[] = "--{$name}";
                }
            }
            $flag        = implode(', ', $flags);
            $description = $option['description'] ?? '';
            println("  {$flag}\t{$description}");
        }
    }

    /**
     * 调用命令
     * @param string $command
     */
    public function callCommand(string $command) {
        // 生成类名，方法名
        $class = '';
        if (!$this->isSingleCommand) {
            if (!isset($this->commands[$command])) {
                $script = Argument::script();
                throw new NotFoundException("'{$command}' is not command, see '{$script} --help'.");
            }
            $class = $this->commands[$command];
            if (is_array($class)) {
                $class = array_shift($class);
            }
        } else {
            $tmp   = $this->commands;
            $class = array_shift($tmp);
        }
        $method = 'main';
        // 命令行选项效验
        $this->validateOptions($command);
        // 协程执行
        list($enable, $options) = $this->coroutine;
        if ($enable) {
            // 环境效验
            if (!extension_loaded('swoole') || !class_exists(Scheduler::class)) {
                throw new \RuntimeException('Application has coroutine enabled, require swoole extension >= v4.4 to run. install: https://www.swoole.com/');
            }
            // 触发执行命令前置事件
            $this->eventDispatcher->dispatch(new CommandBeforeExecuteEvent($class));
            // 协程执行
            $scheduler = new Scheduler;
            $scheduler->set($options);
            $scheduler->add(function () use ($class, $method) {
                if ( Coroutine::getCid() == -1) {
                    go([$this, 'callMethod'], $class, $method);
                } else {
                    try {
                        call_user_func([$this, 'callMethod'], $class, $method);
                    } catch (\Throwable $e) {
                        $this->error->handleException($e);
                    }
                }
            });
            $scheduler->start();
            return;
        }
        // 触发执行命令前置事件
        $this->eventDispatcher->dispatch(new CommandBeforeExecuteEvent($class));
        // 普通执行
        $this->callMethod($class, $method);
    }
    /**
     * 调用方法
     * @param $class
     * @param $method
     */
    public function callMethod($class, $method) {
        // 判断类是否存在
        if (!class_exists($class)) {
            throw new NotFoundException("'{$class}' class not found.");
        }
        // 实例化
        $instance = new $class();
        // 判断方法是否存在
        if (!method_exists($instance, $method)) {
            throw new NotFoundException("'{$class}::main' method not found.");
        }
        // 装载
        $this->context->load();
        // 调用方法
        call_user_func([$instance, $method]);
    }
    /**
     * 命令行选项效验
     * @param string $command
     */
    protected function validateOptions(string $command) {
        $options = [];
        if (!$this->isSingleCommand) {
            $options = $this->commands[$command]['options'] ?? [];
        } else {
            $options = $this->commands['options'] ?? [];
        }
        $regflags = [];
        foreach ($options as $option) {
            $names = array_shift($option);
            if (is_string($names)) {
                $names = [$names];
            }
            foreach ($names as $name) {
                if (strlen($name) == 1) {
                    $regflags[] = "-{$name}";
                } else {
                    $regflags[] = "--{$name}";
                }
            }
        }
        foreach (array_keys(Flag::options()) as $flag) {
            if (!in_array($flag, $regflags)) {
                $script  = Argument::script();
                $command = Argument::command();
                $command = $command ? " {$command}" : $command;
                throw new NotFoundException("flag provided but not defined: '{$flag}', see '{$script}{$command} --help'.");
            }
        }
    }
}
