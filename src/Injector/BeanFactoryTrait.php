<?php
declare(strict_types=1);

namespace Karthus\Injector;

use Karthus\Exception\NotFoundException;

/**
 * Trait BeanFactoryTrait
 *
 * @package Karthus\Injector
 */
Trait BeanFactoryTrait {
    /**
     * Bean配置
     * @var array
     */
    public $config = [];
    /**
     * Bean数组
     * @var BeanDefinition[]
     */
    protected $_definitions = [];
    /**
     * 单例池
     * @var array
     */
    protected $_objects = [];
    /**
     * 初始化
     */
    public function init() {
        $definitions = [];
        foreach ($this->config as $config) {
            $definition         = new BeanDefinition($this, $config);
            $name               = $definition->getName();
            $definitions[$name] = $definition;
        }
        $this->_definitions = $definitions;
    }
    /**
     * 装载
     * 实例化所有单例 (Scope == SINGLETON)
     * 解决 BeanInjector 使用了 PhpDocReader 获取注释类型时使用了 file 操作，会导致 SWOOLE_HOOK_FILE 切换协程，使单例失效
     * 该方法必须在执行任何业务逻辑前执行
     */
    public function load() {
        foreach ($this->_definitions as $definition) {
            if ($definition->getScope() == BeanDefinition::SINGLETON) {
                $this->get($definition->getName());
            }
        }
    }
    /**
     * 获取BeanDefinition
     * @param $beanName
     * @return BeanDefinition
     */
    public function getBeanDefinition(string $beanName): BeanDefinition {
        if (!isset($this->_definitions[$beanName])) {
            throw new NotFoundException("Bean definition not found: {$beanName}");
        }
        return $this->_definitions[$beanName];
    }
    /**
     * 获取Bean
     * @param string $beanName
     * @param array $config
     * @return object
     */
    public function getBean(string $beanName, array $config = []) {
        $beanDefinition = $this->getBeanDefinition($beanName);
        // singleton
        if ($beanDefinition->getScope() == BeanDefinition::SINGLETON) {
            if (isset($this->_objects[$beanName])) {
                return $this->_objects[$beanName];
            }
            $object                    = $beanDefinition->newInstance($config);
            $this->_objects[$beanName] = $object;
            return $object;
        }
        // prototype
        return $beanDefinition->newInstance($config);
    }
}
