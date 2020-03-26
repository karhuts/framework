<?php
declare(strict_types=1);
namespace Karthus\Component;

use Swoole\Table;
/**
 * Class Table
 *
 * @package Karthus\Component
 */
class Tables {
    use Singleton;

    const TYPE_INT      = Table::TYPE_INT;
    const TYPE_FLOAT    = Table::TYPE_FLOAT;
    const TYPE_STRING   = Table::TYPE_STRING;

    private $list = [];


    /**
     * @param $name
     * @param array $columns    ['col'=>['type'=>Table::TYPE_STRING,'size'=>1]]
     * @param int $size
     */
    public function add($name,array $columns,$size = 1024):void {
        if(!isset($this->list[$name])){
            $table = new Table($size);
            foreach ($columns as $column => $item){
                $table->column($column,$item['type'],$item['size']);
            }
            $table->create();
            $this->list[$name] = $table;
        }
    }

    /**
     * @param $name
     * @return Table|null
     */
    public function get($name):?Table {
        if(isset($this->list[$name])){
            return $this->list[$name];
        }else{
            return null;
        }
    }
}
