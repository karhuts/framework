<?php
declare(strict_types=1);

namespace Karthus\Functions\Aop;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
class ProxyClassNameVisitor extends NodeVisitorAbstract {
    /**
     * @var string
     */
    private $proxyClassName;

    /**
     * ProxyClassNameVisitor constructor.
     *
     * @param string $proxyClassName
     */
    public function __construct(string $proxyClassName) {
        if (strpos($proxyClassName, '\\') !== false) {
            $exploded = explode('\\', $proxyClassName);
            $proxyClassName = end($exploded);
        }
        $this->proxyClassName = $proxyClassName;
    }

    /**
     * @param Node $node
     * @return int|Node|Node[]|null
     */
    public function leaveNode(Node $node) {
        // Rewirte the class name and extends the original class.
        if ($node instanceof Node\Stmt\Class_ && ! $node->isAnonymous()) {
            $node->extends = new Node\Name($node->name->name);
            $node->name = new Node\Identifier($this->proxyClassName);
            return $node;
        }
    }
}
