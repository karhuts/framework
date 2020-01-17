<?php
declare(strict_types=1);
namespace Karthus;

use Karthus\Component\Singleton;
use Karthus\Config\AbstractConfig;
use Karthus\Config\TableConfig;

class Config {
    /**
     * @var AbstractConfig|TableConfig|null
     */
    private $conf;

    use Singleton;

    /**
     * Config constructor.
     *
     * @param AbstractConfig|null $config
     */
    public function __construct(?AbstractConfig $config = null) {
        if($config == null){
            $config = new TableConfig();
        }
        $this->conf = $config;
    }

    /**
     * @param AbstractConfig $config
     * @return Config
     */
    public function storageHandler(AbstractConfig $config):Config {
        $this->conf = $config;
        return $this;
    }

    /**
     * 获取配置项
     * @param string $keyPath 配置项名称 支持点语法
     * @return array|mixed|null
     */
    public function getConf($keyPath = '') {
        if ($keyPath == '') {
            return $this->toArray();
        }
        return $this->conf->getConf($keyPath);
    }


    /**
     * @param $keyPath
     * @param $data
     * @return bool
     */
    public function setConf($keyPath, $data): bool {
        return $this->conf->setConf($keyPath, $data);
    }


    /**
     * @return array
     */
    public function toArray(): array {
        return $this->conf->getConf();
    }


    /**
     * @param array $conf
     * @return bool
     */
    public function load(array $conf): bool {
        return $this->conf->load($conf);
    }

    /**
     * @param array $conf
     * @return bool
     */
    public function merge(array $conf):bool {
        return $this->conf->merge($conf);
    }

    /**
     * 载入一个文件的配置项
     * @param string $filePath 配置文件路径
     * @param bool   $merge    是否将内容合并入主配置
     */
    public function loadFile($filePath, $merge = false) {
        if (is_file($filePath)) {
            $confData = require_once $filePath;
            if (is_array($confData) && !empty($confData)) {
                $basename = strtolower(basename($filePath, '.php'));
                if (!$merge) {
                    $this->conf->setConf($basename,$confData);
                } else {
                    $this->conf->merge($confData);
                }
            }
        }
    }

    /**
     * @param string $file
     * @throws \Exception
     */
    public function loadEnv(string $file) {
        if(file_exists($file)){
            $data = require $file;
            if(is_array($data)){
                $this->load($data);
            }
        }else{
            throw new \Exception("config file : {$file} is miss");
        }
    }

    /**
     * @return bool
     */
    public function clear():bool {
        return $this->conf->clear();
    }
}
