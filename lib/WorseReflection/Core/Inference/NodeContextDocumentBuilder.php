<?php

namespace Phpactor\WorseReflection\Core\Inference;

use Microsoft\PhpParser\Node;

class NodeContextDocumentBuilder
{
    public function __construct(private NodeContextResolver $resolver)
    {
    }

    public function build(Node $node): NodeContextDocument
    {

        foreach ($node->getDescendantNodes() as $node) {
            $context = $this->resolver->resolveNode($frame, $node);
        }
    }
}
