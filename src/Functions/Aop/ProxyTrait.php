<?php
declare(strict_types=1);

namespace Karthus\Functions\Aop;
use Closure;
use Karthus\Annotation\AnnotationCollector;
use Karthus\Annotation\AspectCollector;
use Karthus\Functions\ReflectionManager;
use Karthus\Service\ApplicationContext;

trait ProxyTrait {
    protected static function __proxyCall(
        string $originalClassName,
        string $method,
        array $arguments,
        Closure $closure
    ) {
        $proceedingJoinPoint = new ProceedingJoinPoint($closure, $originalClassName, $method, $arguments);
        $result = self::handleAround($proceedingJoinPoint);
        unset($proceedingJoinPoint);
        return $result;
    }

    /**
     *
     * @TODO This method will be called everytime, should optimize it later.
     * @param string $className
     * @param string $method
     * @param array  $args
     * @return array
     * @throws \ReflectionException
     */
    protected static function getParamsMap(string $className, string $method, array $args): array {
        $map = [
            'keys' => [],
            'order' => [],
        ];
        $reflectMethod = ReflectionManager::reflectMethod($className, $method);
        $reflectParameters = $reflectMethod->getParameters();
        $leftArgCount = count($args);
        foreach ($reflectParameters as $key => $reflectionParameter) {
            $arg = $reflectionParameter->isVariadic() ? $args : array_shift($args);
            if (! isset($arg) && $leftArgCount <= 0) {
                $arg = $reflectionParameter->getDefaultValue();
            }
            --$leftArgCount;
            $map['keys'][$reflectionParameter->getName()] = $arg;
            $map['order'][] = $reflectionParameter->getName();
        }
        return $map;
    }

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     */
    private static function handleAround(ProceedingJoinPoint $proceedingJoinPoint) {
        $aspects = self::getAspects($proceedingJoinPoint->className, $proceedingJoinPoint->methodName);
        $annotationAspects = self::getAnnotationAspects($proceedingJoinPoint->className, $proceedingJoinPoint->methodName);
        $aspects = array_unique(array_merge($aspects, $annotationAspects));
        if (empty($aspects)) {
            return $proceedingJoinPoint->processOriginalMethod();
        }
        $container = ApplicationContext::getContainer();
        if (method_exists($container, 'make')) {
            $pipeline = $container->make(Pipeline::class);
        } else {
            $pipeline = new Pipeline($container);
        }
        return $pipeline->via('process')
            ->through($aspects)
            ->send($proceedingJoinPoint)
            ->then(function (ProceedingJoinPoint $proceedingJoinPoint) {
                return $proceedingJoinPoint->processOriginalMethod();
            });
    }

    /**
     * @param string $className
     * @param string $method
     * @return array
     */
    private static function getAspects(string $className, string $method): array {
        $aspects = AspectCollector::get('classes', []);
        $matchedAspect = [];
        foreach ($aspects as $aspect => $rules) {
            foreach ($rules as $rule) {
                if (Aspect::isMatch($className, $method, $rule)) {
                    $matchedAspect[] = $aspect;
                    break;
                }
            }
        }
        // The matched aspects maybe have duplicate aspect, should unique it when use it.
        return $matchedAspect;
    }

    /**
     * @param string $className
     * @param string $method
     * @return array
     */
    private static function getAnnotationAspects(string $className, string $method): array {
        $matchedAspect = $annotations = $rules = [];
        $classAnnotations = AnnotationCollector::get($className . '._c', []);
        $methodAnnotations = AnnotationCollector::get($className . '._m.' . $method, []);
        $annotations = array_unique(array_merge(array_keys($classAnnotations), array_keys($methodAnnotations)));
        if (! $annotations) {
            return $matchedAspect;
        }
        $aspects = AspectCollector::get('annotations', []);
        foreach ($aspects as $aspect => $rules) {
            foreach ($rules as $rule) {
                foreach ($annotations as $annotation) {
                    if (strpos($rule, '*') !== false) {
                        $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $rule);
                        $pattern = "/^{$preg}$/";
                        if (! preg_match($pattern, $annotation)) {
                            continue;
                        }
                    } elseif ($rule !== $annotation) {
                        continue;
                    }
                    $matchedAspect[] = $aspect;
                }
            }
        }
        // The matched aspects maybe have duplicate aspect, should unique it when use it.
        return $matchedAspect;
    }
}
