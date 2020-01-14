<?php
declare(strict_types=1);

namespace Karthus\Injector;

/**
 * Interface BeanFactoryInterface
 *
 * @package Karthus\Injector
 */
interface BeanFactoryInterface {
    /**
     * 初始化
     */
    public function init();
    /**
     * 获取BeanDefinition
     * @param $beanName
     * @return BeanDefinition
     */
    public function getBeanDefinition(string $beanName): BeanDefinition;
    /**
     * 获取Bean
     * @param string $beanName
     * @param array $config
     * @return object
     */
    public function getBean(string $beanName, array $config = []);
}
