<?php
declare(strict_types=1);

namespace Karthus\Task;

use Karthus\Component\Process\AbstractProcess;
use Karthus\Component\Process\Socket\UnixProcessConfig;
use Swoole\Atomic\Long;
use Swoole\Server;
use Swoole\Table;

class Task {
    private $taskIdAtomic;
    private $table;
    private $config;
    private $attachServer = false;

    public const PUSH_IN_QUEUE       = 0;
    public const PUSH_QUEUE_FAIL     = -1;
    public const ERROR_PROCESS_BUSY  = -2;
    public const ERROR_PACKAGE_ERROR = -3;
    public const ERROR_TASK_ERROR    = -4;
    public const ERROR_PACKAGE_EXPIRE = -5;

    /**
     * 初始化
     *
     * Task constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->taskIdAtomic = new Long(0);
        $this->table        = new Table(512);
        $this->table->column('running', Table::TYPE_INT, 4);
        $this->table->column('success', Table::TYPE_INT, 4);
        $this->table->column('fail', Table::TYPE_INT, 4);
        $this->table->column('pid', Table::TYPE_INT, 4);
        $this->table->column('workerIndex', Table::TYPE_INT, 4);
        $this->table->create();
        $this->config       = $config;
    }

    /**
     * 注册到Server中
     *
     * @param Server $server
     */
    public function addToServer(Server $server) {
        $list = $this->initProcess();
        /** @var AbstractProcess $item */
        foreach ($list as $item){
            $server->addProcess($item->getProcess());
        }
        $this->attachServer = true;
    }

    /**
     * @return array
     */
    protected function initProcess():array {
        $ret        = [];
        $serverName = $this->config->getServerName();
        for($i = 0; $i < $this->config->getWorkerNum(); $i++){
            $config = new UnixProcessConfig();
            $config->setProcessName("{$serverName}-TaskWorker-{$i}");
            $config->setSocketFile($this->getUnixID($i));
            $config->setProcessGroup("{$serverName}-TaskWorker");
            $config->setArg([
                'workerIndex'=>$i,
                'infoTable'=>$this->table,
                'taskIdAtomic'=>$this->taskIdAtomic,
                'taskConfig'=>$this->config
            ]);
            $ret[$i] = new Worker($config);
        }
        return  $ret;
    }

    /**
     * 查看异步进程状态
     *
     * @return array
     */
    public function status():array {
        $ret = [];
        foreach ($this->table as $key => $value){
            $ret[$key] = $value;
        }
        return $ret;
    }

    /**
     * @param int $id
     * @return string
     */
    private function getUnixID(int $id):string {
        return $this->config->getTempDir()."/{$this->config->getServerName()}-TaskWorker-{$id}.sock";
    }

}
