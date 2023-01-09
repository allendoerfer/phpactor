<?php

namespace Phpactor\Extension\PHPUnit\Tests\Unit\LanguageServer\CodeActionProvider;

use Amp\CancellationTokenSource;
use Closure;
use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\Extension\PHPUnit\LanguageServer\CodeActionProvider\GeneratieMethodCodeActionProvider;
use Phpactor\LanguageServer\Test\ProtocolFactory;
use Phpactor\WorseReflection\ReflectorBuilder;
use function Amp\Promise\wait;

class GenerateMethodCodeActionProviderTest extends TestCase
{
    /**
     * @dataProvider provideCodeActions
     * @param Closure(CodeAction[]): void $closure
     */
    public function testCodeActions(string $content, Closure $closure): void
    {
        $reflector = ReflectorBuilder::create()->build();
        $cancel = (new CancellationTokenSource)->getToken();
        $provider = new GeneratieMethodCodeActionProvider($reflector);
        $codeActions = wait($provider->provideActionsFor(ProtocolFactory::textDocumentItem('file:///foo', $content), ProtocolFactory::range(0, 0, 0, 0), $cancel));
        $closure($codeActions);

    }
    /**
     * @return Generator<string,array{string,Closure(array): void}>
     */
    public function provideCodeActions(): Generator
    {
        yield 'add setup' => [
            <<<'EOT'
                <?php
                namespace Foobar;

                use PHPUnit\Framework\TestCase;

                class FooTest extends TestCase {
                }
                EOT
            ,
            function (array $codeActions): void {
                self::assertCount(1, $codeActions);
            }
        ];
    }
}
