<?php
declare(strict_types=1);

namespace Karthus\Component\Process;

use Karthus\Component\Singleton;
use Swoole\Process;
use Swoole\Server;
use Swoole\Table;

class Manager {
    use Singleton;

    protected $processList = [];
    protected $table;
    protected $processResource = [];

    /**
     * Manager constructor.
     */
    public function __construct() {
        $this->table = new Table(2048);
        $this->table->column('pid', Table::TYPE_INT, 8);
        $this->table->column('name', Table::TYPE_STRING, 50);
        $this->table->column('group', Table::TYPE_STRING, 50);
        $this->table->column('memoryUsage', Table::TYPE_INT, 8);
        $this->table->column('memoryPeakUsage', Table::TYPE_INT, 8);
        $this->table->create();
    }

    /**
     * @return array
     */
    public function getProcessResource():array {
        return $this->processResource;
    }

    /**
     * @return Table
     */
    public function getProcessTable():Table {
        return $this->table;
    }

    /**
     * @param     $pidOrGroupName
     * @param int $sig
     * @return array
     */
    public function kill($pidOrGroupName, $sig = SIGTERM):array {
        $list     = [];
        if(is_numeric($pidOrGroupName)){
            $info = $this->table->get($pidOrGroupName);
            if($info){
                $list[$pidOrGroupName] = $pidOrGroupName;
            }
        }else{
            foreach ($this->table as $key => $value){
                if($value['group'] === $pidOrGroupName){
                    $list[$key] = $value;
                }
            }
        }
        $this->clearPid($list);
        foreach ($list as $pid){
            Process::kill($pid,$sig);
        }
        return $list;
    }

    /**
     * @param null $pidOrGroupName
     * @return array
     */
    public function info($pidOrGroupName = null): array {
        $list = [];
        if($pidOrGroupName === null){
            foreach ($this->table as $pid =>$value){
                $list[$pid] = $value;
            }
        }else if(is_numeric($pidOrGroupName)){
            $info = $this->table->get($pidOrGroupName);
            if($info){
                $list[$pidOrGroupName] = $info;
            }
        }else{
            foreach ($this->table as $key => $value){
                if($value['group'] === $pidOrGroupName){
                    $list[$key] = $value;
                }
            }
        }

        $sort = array_column($list,'group');
        array_multisort($sort,SORT_DESC,$list);
        foreach ($list as $key => $value){
            unset($list[$key]);
            $list[$value['pid']] = $value;
        }
        return $this->clearPid($list);
    }

    /**
     * @param AbstractProcess $process
     * @return $this
     */
    public function addProcess(AbstractProcess $process): Manager{
        $this->processList[] = $process;
        return $this;
    }

    /**
     * @param Server $server
     */
    public function addToServer(Server $server): void {
        /** @var AbstractProcess $process */
        foreach ($this->processList as $process) {
            $server->addProcess($process->getProcess());
        }
    }

    /**
     * @param int $pid
     * @return bool|mixed
     */
    public function pidExist(int $pid): bool {
        return Process::kill($pid,0);
    }

    /**
     * @param array $list
     * @return array
     */
    protected function clearPid(array $list): array {
        foreach ($list as $pid => $value){
            if(!$this->pidExist($pid)){
                $this->table->del($pid);
                unset($list[$pid]);
            }
        }
        return $list;
    }

    /**
     * @param AbstractProcess $process
     */
    public function addProcessResource(AbstractProcess $process) {
        $this->processResource[] = $process;
    }
}