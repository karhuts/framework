<?php
declare(strict_types=1);
namespace Karthus\Process;

use Swoole\Coroutine\Scheduler;
use Swoole\Event;
use Swoole\Process;
use Swoole\Timer;

abstract class AbstractProcess {
    /**
     * @var Process
     */
    private $swooleProcess;
    /** @var Config */
    private $config;

    /**
     * AbstractProcess constructor.
     *
     * @param array $args
     */
    public function __construct(...$args) {
        $arg1 = array_shift($args);
        if ($arg1 instanceof Config) {
            $this->config = $arg1;
        } else {
            $this->config = new Config();
            $this->config->setProcessName($arg1);
            $arg = array_shift($args);
            $this->config->setArg($arg);
            $redirectStdinStdout = (bool) array_shift($args) ?: false;
            $this->config->setRedirectStdinStdout($redirectStdinStdout);
            $pipeType = array_shift($args);
            $pipeType = $pipeType === null ? Config::PIPE_TYPE_SOCK_DGRAM : $pipeType;
            $this->config->setPipeType($pipeType);
            $enableCoroutine = (bool) array_shift($args) ?: false;
            $this->config->setEnableCoroutine($enableCoroutine);
        }
        $this->swooleProcess = new Process([$this, '__start'], $this->config->isRedirectStdinStdout(), $this->config->getPipeType(), $this->config->isEnableCoroutine());
    }

    public function getProcess(): Process {
        return $this->swooleProcess;
    }

    /**
     * TODO 天假定时器
     *
     * @param          $ms
     * @param callable $call
     * @return int|null
     */
    public function addTick($ms, callable $callback): ?int {
    }

    /**
     * TODO 清除定时器
     *
     * @param int $timerId
     * @return int|null
     */
    public function clearTick(int $timerId): ?int {
    }

    /**
     *
     * TODO 延迟定时器
     * @param          $ms
     * @param callable $call
     * @return int|null
     */
    public function delay($ms, callable $callback): ?int {
    }


    /**
     *
     * 获取PID
     * 服务启动后才能获得到pid
     *
     * @return int|null
     */
    public function getPid(): ?int {
        if (isset($this->swooleProcess->pid)) {
            return $this->swooleProcess->pid;
        } else {
            return null;
        }
    }

    /**
     * 开始执行
     *
     * @param Process $process
     * @throws \Throwable
     */
    public function __start(Process $process) {
        if (!in_array(PHP_OS, ['Darwin', 'CYGWIN', 'WINNT']) && !empty($this->getProcessName())) {
            $process->name($this->getProcessName());
        }
        swoole_event_add($this->swooleProcess->pipe, function () {
            try {
                $this->onPipeReadable($this->swooleProcess);
            } catch (\Throwable $throwable) {
                $this->onException($throwable);
            }
        });
        Process::signal(SIGTERM, function () use ($process) {
            swoole_event_del($process->pipe);
            /*
             * 清除全部定时器
             */
            Timer::clearAll();
            Process::signal(SIGTERM, null);
            Event::exit();
        });
        register_shutdown_function(function () {
            $schedule = new Scheduler();
            $schedule->add(function () {
                try {
                    $this->onShutDown();
                } catch (\Throwable $throwable) {
                    $this->onException($throwable);
                }
                Timer::clearAll();
            });
            $schedule->start();
        });

        try {
            $this->run($this->config->getArg());
        } catch (\Throwable $throwable) {
            $this->onException($throwable);
        }
    }

    /**
     * 获取参数
     *
     * @return mixed
     */
    public function getArg() {
        return $this->config->getArg();
    }

    /**
     * 获取进程名称
     *
     * @return mixed
     */
    public function getProcessName() {
        return $this->config->getProcessName();
    }

    /**
     * 获取配置
     *
     * @return Config
     */
    protected function getConfig(): Config {
        return $this->config;
    }

    /**
     * @param \Throwable $throwable
     * @param mixed      ...$args
     * @throws \Throwable
     */
    protected function onException(\Throwable $throwable, ...$args) {
        throw $throwable;
    }

    /**
     * 运行
     *
     * @param $arg
     * @return mixed
     */
    protected abstract function run($arg);

    /**
     * 关闭
     */
    protected function onShutDown() {}

    /**
     * @param Process $process
     */
    protected function onPipeReadable(Process $process) {
        $process->read();
    }
}
