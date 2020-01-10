<?php
declare(strict_types=1);

namespace Karthus\Functions\Aop;

use Karthus\Functions\Composer;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use function value;

class Ast {
    /**
     * @var \PhpParser\Parser
     */
    private $astParser;
    /**
     * @var PrettyPrinterAbstract
     */
    private $printer;

    /**
     * Ast constructor.
     */
    public function __construct() {
        $parserFactory = new ParserFactory();
        $this->astParser = $parserFactory->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard();
    }

    /**
     * @param string $code
     *
     * @return array|null
     */
    public function parse(string $code): ?array {
        return $this->astParser->parse($code);
    }

    /**
     * @param string $className
     * @param string $proxyClassName
     * @return mixed
     */
    public function proxy(string $className, string $proxyClassName) {
        $stmts = AstCollector::get($className, value(function () use ($className) {
            $code = $this->getCodeByClassName($className);
            return $stmts = $this->astParser->parse($code);
        }));
        $traverser = new NodeTraverser();
        // @TODO Allow user modify or replace node vistor.
        $traverser->addVisitor(new ProxyClassNameVisitor($proxyClassName));
        $traverser->addVisitor(new ProxyCallVisitor($className));
        $modifiedStmts = $traverser->traverse($stmts);
        return $this->printer->prettyPrintFile($modifiedStmts);
    }

    /**
     * @param array $stmts
     * @return string
     */
    public function parseClassByStmts(array $stmts): string {
        $namespace = $className = '';
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Namespace_ && $stmt->name) {
                $namespace = $stmt->name->toString();
                foreach ($stmt->stmts as $node) {
                    if ($node instanceof Class_ && $node->name) {
                        $className = $node->name->toString();
                        break;
                    }
                }
            }
        }
        return ($namespace && $className) ? $namespace . '\\' . $className : '';
    }

    /**
     * @param string $className
     * @return string
     */
    private function getCodeByClassName(string $className): string {
        $file = Composer::getLoader()->findFile($className);
        if (! $file) {
            return '';
        }
        return file_get_contents($file);
    }
}
