<?php

namespace Phpactor\WorseReflection\Core\Inference;

use Microsoft\PhpParser\Node;

class NodeContextDocumentBuilder
{
    public function __construct(private NodeContextResolver $resolver)
    {
    }

    public function build(Node $node, ?Frame $frame = null): NodeContext
    {
        $frame = $frame ?: new Frame();
        $nodeContext = $this->resolver->resolveNode($frame, $node);
        foreach ($node->getChildNodes() as $child) {
            $nodeContext->addChild($this->build($child, $frame));
        }

        return $nodeContext;
    }
}
