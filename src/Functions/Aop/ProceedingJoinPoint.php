<?php
declare(strict_types=1);
namespace Karthus\Functions\Aop;
use Closure;
use Karthus\Annotation\AnnotationCollector;
use Karthus\Annotation\AnnotationMetadata;
use Karthus\Functions\ReflectionManager;
use function value;

class ProceedingJoinPoint {
    /**
     * @var string
     */
    public $className;
    /**
     * @var string
     */
    public $methodName;
    /**
     * @var mixed[]
     */
    public $arguments;
    /**
     * @var mixed
     */
    public $result;
    /**
     * @var Closure
     */
    public $originalMethod;
    /**
     * @var null|Closure
     */
    public $pipe;

    /**
     * ProceedingJoinPoint constructor.
     *
     * @param Closure $originalMethod
     * @param string  $className
     * @param string  $methodName
     * @param array   $arguments
     */
    public function __construct(Closure $originalMethod, string $className, string $methodName, array $arguments) {
        $this->originalMethod = $originalMethod;
        $this->className = $className;
        $this->methodName = $methodName;
        $this->arguments = $arguments;
    }

    /**
     * Delegate to the next aspect.
     *
     * @throws \Exception
     */
    public function process() {
        $closure = $this->pipe;
        if (! $closure instanceof Closure) {
            throw new \Exception('The pipe is not instanceof \Closure');
        }
        return $closure($this);
    }
    /**
     * Process the original method, this method should trigger by pipeline.
     */
    public function processOriginalMethod() {
        $this->pipe = null;
        $closure = $this->originalMethod;
        if (count($this->arguments['keys']) > 1) {
            $arguments = $this->getArguments();
        } else {
            $arguments = array_values($this->arguments['keys']);
        }
        return $closure(...$arguments);
    }

    /**
     * @return AnnotationMetadata
     */
    public function getAnnotationMetadata(): AnnotationMetadata {
        $metadata = AnnotationCollector::get($this->className);
        return new AnnotationMetadata($metadata['_c'] ?? [], $metadata['_m'][$this->methodName] ?? []);
    }

    /**
     * @return mixed
     */
    public function getArguments() {
        return value(function () {
            $result = [];
            foreach ($this->arguments['order'] ?? [] as $order) {
                $result[] = $this->arguments['keys'][$order];
            }
            return $result;
        });
    }

    /**
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    public function getReflectMethod(): \ReflectionMethod {
        return ReflectionManager::reflectMethod(
            $this->className,
            $this->methodName
        );
    }
}
