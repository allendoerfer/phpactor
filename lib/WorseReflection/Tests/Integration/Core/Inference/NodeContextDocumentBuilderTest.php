<?php

namespace Phpactor\WorseReflection\Tests\Integration\Core\Inference;

use Microsoft\PhpParser\Parser;
use Phpactor\WorseReflection\Bridge\Phpactor\DocblockParser\DocblockParserFactory;
use Phpactor\WorseReflection\Core\Cache\NullCache;
use Phpactor\WorseReflection\Core\DefaultResolverFactory;
use Phpactor\WorseReflection\Core\Inference\GenericMapResolver;
use Phpactor\WorseReflection\Core\Inference\NodeContextBuilder;
use Phpactor\WorseReflection\Core\Inference\NodeContextResolver;
use Phpactor\WorseReflection\Core\Inference\NodeToTypeConverter;
use Phpactor\WorseReflection\Core\Inference\Resolver\MemberAccess\NodeContextFromMemberAccess;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\WorseReflection\Tests\Integration\IntegrationTestCase;
use Psr\Log\NullLogger;

class NodeContextDocumentBuilderTest extends IntegrationTestCase
{
    public function testBuild(): void
    {
        $source = <<<'EOT'
        <?php 

        class Foobar {
            private int $foobar;
            public function __construct()
            {
                $this->foobar = 123;
            }
        }
        EOT;
        $node = (new Parser())->parseSourceFile($source);
        $reflector = ReflectorBuilder::create()->build();
        $context = (new NodeContextBuilder(new NodeContextResolver(
            $reflector,
            new DocblockParserFactory($reflector),
            new NullLogger(),
            new NullCache(),
            (new DefaultResolverFactory(
                $reflector,
                new NodeToTypeConverter($reflector),
                new GenericMapResolver($reflector),
                new NodeContextFromMemberAccess(new GenericMapResolver($reflector), []),
            ))->createResolvers()
        )))->build($node);

        dump($context->__toString());
    }
}
