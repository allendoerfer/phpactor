<?php

namespace Phpactor\WorseReflection\Core\Inference;

use Microsoft\PhpParser\Node;

class NodeContextBuilder
{
    public function __construct(private NodeContextResolver $resolver)
    {
    }

    public function build(Node $node, ?Node $target = null, ?Frame $frame = null): NodeContext
    {
        $frame = $frame ?: new Frame();
        $nodeContext = $this->resolver->resolveNode($frame, $node);
        foreach ($node->getChildNodes() as $child) {
            $childContext = $this->build($child, $target, $frame);
            $nodeContext->addChild($childContext);
            if ($child === $target) {
                return $childContext;
            }
        }

        return $nodeContext;
    }
}
