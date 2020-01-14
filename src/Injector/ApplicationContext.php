<?php
declare(strict_types=1);
namespace Karthus\Injector;

use Psr\Container\ContainerInterface;

/**
 * Class ApplicationContext
 *
 * @package Karthus\Injector
 */
class ApplicationContext implements BeanFactoryInterface, ContainerInterface {
    use BeanFactoryTrait;
    /**
     * ApplicationContext constructor.
     * @param array $config
     */
    public function __construct(array $config) {
        // 导入属性
        $this->config = $config;
        // 初始化
        $this->init();
    }
    /**
     * 获取Bean
     * @param string $beanName
     * @return object
     */
    public function get($beanName) {
        return $this->getBean($beanName);
    }
    /**
     * 判断Bean是否存在
     * @param string $beanName
     * @return bool
     */
    public function has($beanName) {
        $beanDefinition = null;
        try {
            $beanDefinition = $this->getBeanDefinition($beanName);
        } catch (\Throwable $e) {
        }
        return $beanDefinition ? true : false;
    }
}
