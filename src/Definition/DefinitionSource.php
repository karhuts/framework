<?php
declare(strict_types=1);
namespace Karthus\Definition;

use Karthus\Annotation\AnnotationCollector;
use Karthus\Annotation\AspectCollector;
use Karthus\Annotation\Scanner;
use Karthus\Contract\DefinitionInterface;
use Karthus\Contract\DefinitionSourceInterface;
use Karthus\Functions\Aop\AstCollector;
use Karthus\Functions\ReflectionManager;
use Karthus\Functions\Strings;
use ReflectionClass;
use ReflectionFunctionAbstract;
use Symfony\Component\Finder\Finder;
use function class_exists;
use function count;
use function explode;
use function feof;
use function fgets;
use function file_exists;
use function file_put_contents;
use function filemtime;
use function fopen;
use function implode;
use function interface_exists;
use function is_callable;
use function is_dir;
use function is_readable;
use function is_string;
use function md5;
use function method_exists;
use function preg_match;
use function print_r;
use function str_replace;
use function trim;

class DefinitionSource implements DefinitionSourceInterface {
    /**
     * @var bool
     */
    private $enableCache = false;
    /**
     * Path of annotation meta data cache.
     *
     * @var string
     */
    private $cachePath = BASE_PATH . '/runtime/container/annotations';
    /**
     * @var array
     */
    private $source;
    /**
     * @var Scanner
     */
    private $scanner;

    /**
     * DefinitionSource constructor.
     *
     * @param array      $source
     * @param ScanConfig $scanConfig
     * @param bool       $enableCache
     */
    public function __construct(array $source, ScanConfig $scanConfig, bool $enableCache = false) {
        $this->scanner = new Scanner($scanConfig->getIgnoreAnnotations());
        $this->enableCache = $enableCache;
        // Scan the specified paths and collect the ast and annotations.
        $this->scan($scanConfig->getDirs(), $scanConfig->getCollectors());
        $this->source = $this->normalizeSource($source);
    }


    /**
     * Returns the DI definition for the entry name.
     */
    public function getDefinition(string $name): ?DefinitionInterface {
        if (! isset($this->source[$name])) {
            $this->source[$name] = $this->autowire($name);
        }
        return $this->source[$name];
    }

    /**
     * @return array definitions indexed by their name
     */
    public function getDefinitions(): array {
        return $this->source;
    }

    /**
     * @param array|callable|string $definition
     * @return DefinitionSource
     */
    public function addDefinition(string $name, $definition): self {
        $this->source[$name] = $this->normalizeDefinition($name, $definition);
        return $this;
    }

    public function clearDefinitions(): void {
        $this->source = [];
    }

    /**
     * Read the type-hinting from the parameters of the function.
     *
     * @param ReflectionFunctionAbstract $constructor
     * @return array
     */
    private function getParametersDefinition(ReflectionFunctionAbstract $constructor): array {
        $parameters = [];
        foreach ($constructor->getParameters() as $index => $parameter) {
            // Skip optional parameters.
            if ($parameter->isOptional()) {
                continue;
            }
            $parameterClass = $parameter->getClass();
            if ($parameterClass) {
                $parameters[$index] = new Reference($parameterClass->getName());
            }
        }
        return $parameters;
    }

    /**
     * Normaliaze the user definition source to a standard definition souce.
     *
     * @param array $source
     * @return array
     */
    private function normalizeSource(array $source): array {
        $definitions = [];
        foreach ($source as $identifier => $definition) {
            $normalizedDefinition = $this->normalizeDefinition($identifier, $definition);
            if (! is_null($normalizedDefinition)) {
                $definitions[$identifier] = $normalizedDefinition;
            }
        }
        return $definitions;
    }

    /**
     * @param string                $identifier
     * @param array|callable|string $definition
     * @return DefinitionInterface|null
     */
    private function normalizeDefinition(string $identifier, $definition): ?DefinitionInterface {
        if (is_string($definition) && class_exists($definition)) {
            if (method_exists($definition, '__invoke')) {
                return new FactoryDefinition($identifier, $definition, []);
            }
            return $this->autowire($identifier, new ObjectDefinition($identifier, $definition));
        }
        if (is_callable($definition)) {
            return new FactoryDefinition($identifier, $definition, []);
        }
        return null;
    }

    /**
     * @param string                $name
     * @param ObjectDefinition|null $definition
     * @return ObjectDefinition|null
     */
    private function autowire(string $name, ObjectDefinition $definition = null): ?ObjectDefinition {
        $className = $definition ? $definition->getClassName() : $name;
        if (! class_exists($className) && ! interface_exists($className)) {
            return $definition;
        }
        $definition = $definition ?: new ObjectDefinition($name);
        /**
         * Constructor.
         */
        $class = ReflectionManager::reflectClass($className);
        $constructor = $class->getConstructor();
        if ($constructor && $constructor->isPublic()) {
            $constructorInjection = new MethodInjection('__construct', $this->getParametersDefinition($constructor));
            $definition->completeConstructorInjection($constructorInjection);
        }
        /**
         * Properties.
         */
        $propertiesMetadata = AnnotationCollector::get($className);
        $propertyHandlers = PropertyHandlerManager::all();
        if (isset($propertiesMetadata['_p'])) {
            foreach ($propertiesMetadata['_p'] as $propertyName => $value) {
                // Because `@Inject` is a internal logical of DI component, so leave the code here.
                /** @var Inject $injectAnnotation */
                if ($injectAnnotation = $value[Inject::class] ?? null) {
                    $propertyInjection = new PropertyInjection($propertyName, new Reference($injectAnnotation->value));
                    $definition->addPropertyInjection($propertyInjection);
                }
                // Handle PropertyHandler mechanism.
                foreach ($value as $annotationClassName => $annotationObject) {
                    if (isset($propertyHandlers[$annotationClassName])) {
                        foreach ($propertyHandlers[$annotationClassName] ?? [] as $callback) {
                            call($callback, [$definition, $propertyName, $annotationObject]);
                        }
                    }
                }
            }
        }
        $definition->setNeedProxy($this->isNeedProxy($class));
        return $definition;
    }

    /**
     * @param array $paths
     * @param array $collectors
     * @return bool
     */
    private function scan(array $paths, array $collectors): bool {
        $appPaths = $vendorPaths = [];
        /**
         * If you are a hyperf developer
         * this value will be your local path, like hyperf/src.
         * @var string
         */
        $ident = 'vendor';
        $isDefinedBasePath = defined('BASE_PATH');
        foreach ($paths as $path) {
            if ($isDefinedBasePath) {
                if (Strings::startsWith($path, BASE_PATH . '/' . $ident)) {
                    $vendorPaths[] = $path;
                } else {
                    $appPaths[] = $path;
                }
            } else {
                if (strpos($path, $ident) !== false) {
                    $vendorPaths[] = $path;
                } else {
                    $appPaths[] = $path;
                }
            }
        }
        $this->loadMetadata($appPaths, 'app');
        $this->loadMetadata($vendorPaths, 'vendor');
        return true;
    }

    /**
     * @param array $paths
     * @param       $type
     * @return bool
     */
    private function loadMetadata(array $paths, $type) {
        if (empty($paths)) {
            return true;
        }
        $cachePath = $this->cachePath . '.' . $type . '.cache';
        $pathsHash = md5(implode(',', $paths));
        if ($this->hasAvailableCache($paths, $pathsHash, $cachePath)) {
            $this->printLn('Detected an available cache, skip the ' . $type . ' scan process.');
            [, $serialized] = explode(PHP_EOL, file_get_contents($cachePath));
            $this->scanner->collect(unserialize($serialized));
            return false;
        }
        $this->printLn('Scanning ' . $type . ' ...');
        $startTime = microtime(true);
        $meta = $this->scanner->scan($paths);
        foreach ($meta as $className => $stmts) {
            AstCollector::set($className, $stmts);
        }
        $useTime = microtime(true) - $startTime;
        $this->printLn('Scan ' . $type . ' completed, took ' . $useTime * 1000 . ' milliseconds.');
        if (! $this->enableCache) {
            return true;
        }
        // enableCache: set cache
        if (! file_exists($cachePath)) {
            $exploded = explode('/', $cachePath);
            unset($exploded[count($exploded) - 1]);
            $dirPath = implode('/', $exploded);
            if (! is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
            }
        }
        $data = implode(PHP_EOL, [$pathsHash, serialize(array_keys($meta))]);
        file_put_contents($cachePath, $data);
        return true;
    }

    /**
     * @param array  $paths
     * @param string $pathsHash
     * @param string $filename
     * @return bool
     */
    private function hasAvailableCache(array $paths, string $pathsHash, string $filename): bool {
        if (! $this->enableCache) {
            return false;
        }
        if (! file_exists($filename) || ! is_readable($filename)) {
            return false;
        }
        $handler = fopen($filename, 'r');
        while (! feof($handler)) {
            $line = fgets($handler);
            if (trim($line) !== $pathsHash) {
                return false;
            }
            break;
        }
        $cacheLastModified = filemtime($filename) ?? 0;
        $finder = new Finder();
        $finder->files()->in($paths)->name('*.php');
        foreach ($finder as $file) {
            if ($file->getMTime() > $cacheLastModified) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $message
     */
    private function printLn(string $message): void {
        print_r($message . PHP_EOL);
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return bool
     */
    private function isNeedProxy(ReflectionClass $reflectionClass): bool {
        $className = $reflectionClass->getName();
        $classesAspects = AspectCollector::get('classes', []);
        foreach ($classesAspects as $aspect => $rules) {
            foreach ($rules as $rule) {
                if ($this->isMatch($rule, $className)) {
                    return true;
                }
            }
        }
        // Get the controller annotations.
        $classAnnotations = value(function () use ($className) {
            $annotations = AnnotationCollector::get($className . '._c', []);
            return array_keys($annotations);
        });
        // Aggregate all methods annotations.
        $methodAnnotations = value(function () use ($className) {
            $defined = [];
            $annotations = AnnotationCollector::get($className . '._m', []);
            foreach ($annotations as $method => $annotation) {
                $defined = array_merge($defined, array_keys($annotation));
            }
            return $defined;
        });
        $annotations = array_unique(array_merge($classAnnotations, $methodAnnotations));
        if ($annotations) {
            $annotationsAspects = AspectCollector::get('annotations', []);
            foreach ($annotationsAspects as $aspect => $rules) {
                foreach ($rules as $rule) {
                    foreach ($annotations as $annotation) {
                        if ($this->isMatch($rule, $annotation)) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param string $rule
     * @param string $target
     * @return bool
     */
    private function isMatch(string $rule, string $target): bool {
        if (strpos($rule, '::') !== false) {
            [$rule,] = explode('::', $rule);
        }
        if (strpos($rule, '*') === false && $rule === $target) {
            return true;
        }
        $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $rule);
        $pattern = "/^{$preg}$/";
        if (preg_match($pattern, $target)) {
            return true;
        }
        return false;
    }
}
