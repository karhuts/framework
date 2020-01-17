<?php
declare(strict_types=1);
namespace Karthus\Config;

use Karthus\Spl\SplArray;
use Swoole\Table;

class TableConfig extends AbstractConfig {

    /**
     * @var Table
     */
    private $table;

    /**
     * 初始化表格
     *
     * TableConfig constructor.
     */
    public function __construct() {
        $this->table = new Table(1024);
        $this->table->column('data', Table::TYPE_STRING, 2048);
        $this->table->create();
    }

    /**
     * @param null $key
     * @return array|mixed|null
     */
    public function getConf($key = null) {
        if ($key == null) {
            $data = [];
            foreach ($this->table as $key => $item) {
                $data[$key] = unserialize($item['data']);
            }
            return $data;
        }
        if (strpos($key, ".") > 0) {
            $temp = explode(".", $key);
            $data = $this->table->get(array_shift($temp));
            if ($data) {
                $data = unserialize($data['data']);
                /*
                 * 数组才有意义进行二次搜索
                 */
                if (is_array($data)) {
                    $data = new SplArray($data);
                    return $data->get(implode('.', $temp));
                }
            }
        } else {
            $data = $this->table->get($key);
            if ($data) {
                return unserialize($data['data']);
            }
        }
        return null;
    }

    /**
     * @param $key
     * @param $val
     * @return bool
     */
    public function setConf($key, $val): bool {
        if (strpos($key, ".") > 0) {
            $temp = explode(".", $key);
            $key = array_shift($temp);
            $data = $this->getConf($key);
            if (is_array($data)) {
                $data = new SplArray($data);
            } else {
                $data = new SplArray();
            }
            $data->set(implode('.', $temp), $val);
            return $this->table->set($key, [
                'data' => serialize($data->getArrayCopy())
            ]);
        } else {
            return $this->table->set($key, [
                'data' => serialize($val)
            ]);
        }
    }

    /**
     * @param array $array
     * @return bool
     */
    public function load(array $array): bool {
        $this->clear();
        foreach ($array as $key => $value) {
            $this->setConf($key, $value);
        }
        return true;
    }

    /**
     * @param array $array
     * @return bool
     */
    public function merge(array $array): bool {
        foreach ($array as $key => $value) {
            $data = $this->getConf($key);
            if (is_array($data)) {
                $data = $value + $data;
            } else {
                $data = $value;
            }
            $this->setConf($key, $data);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function clear(): bool {
        foreach ($this->table as $key => $item) {
            $this->table->del($key);
        }
        return true;
    }
}
