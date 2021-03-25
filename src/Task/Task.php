<?php
declare(strict_types=1);

namespace Karthus\Task;

use Exception;
use Karthus\Component\Process\AbstractProcess;
use Karthus\Component\Process\Socket\UnixProcessConfig;
use Swoole\Atomic\Long;
use Swoole\Server;
use Swoole\Table;

use function \Opis\Closure\unserialize;
use function \Opis\Closure\serialize;

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
    public function addToServer(Server $server): void {
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

    /**
     * @param               $task
     * @param callable|null $finishCallback
     * @param null          $taskWorkerId
     * @return int|null
     */
    public function async($task,callable $finishCallback = null,$taskWorkerId = null):?int {
        if($taskWorkerId === null){
            $id = $this->findFreeId();
        }else{
            $id = $taskWorkerId;
        }
        if($id !== null){
            $package = new Package();
            $package->setType($package::ASYNC);
            $package->setTask($task);
            $package->setOnFinish($finishCallback);
            $package->setExpire(round(microtime(true) + $this->config->getTimeout() - 0.01,3));
            return $this->sendAndRecv($package,$id);
        }

        return null;
    }

    /*
     * 同步返回执行结果
     */
    public function sync($task,$timeout = 3.0,$taskWorkerId = null) {
        $id = $taskWorkerId ?? $this->findFreeId();
        if($id !== null){
            $package = new Package();
            $package->setType($package::SYNC);
            $package->setTask($task);
            $package->setExpire(round(microtime(true) + $timeout - 0.01,4));
            return $this->sendAndRecv($package,$id,$timeout);
        }

        return null;
    }

    /**
     * 随机找出空闲进程
     *
     * @return int|null
     * @throws Exception
     */
    private function findFreeId():?int {
        mt_srand();
        if($this->attachServer){
            $info = $this->status();
            if(!empty($info)){
                $array_column   = array_column($info,'running');
                array_multisort($array_column,SORT_ASC, $info);
                return $info[0]['workerIndex'];
            }
        }
        return random_int(0, $this->config->getWorkerNum() - 1);
    }

    /**
     * @param Package    $package
     * @param int        $id
     * @param float|null $timeout
     * @return mixed|null
     */
    private function sendAndRecv(Package $package,int $id,float $timeout = null) {
        if($timeout === null){
            $timeout    = $this->config->getTimeout();
        }
        $client         = new UnixClient($this->getUnixID($id));
        $client->send(Protocol::pack(serialize($package)));
        $ret            = $client->recv($timeout);
        $client->close();

        if (!empty($ret)) {
            return unserialize(Protocol::unpack($ret));
        }

        return null;
    }
}
